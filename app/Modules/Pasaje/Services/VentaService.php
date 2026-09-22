<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Modules\Arqueo\Services\ArqueoService;
use App\Shared\Models\AsignacionVehiculoChofer;
use App\Shared\Models\DetalleVenta;
use App\Shared\Models\Encomienda;
use App\Shared\Models\Ingreso;
use App\Shared\Models\Pasajero;
use App\Shared\Models\Ruta;
use App\Shared\Models\VehiculoChoferRuta;
use App\Shared\Models\Venta;
use App\Shared\Models\Viaje;
use App\Shared\Services\QrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VentaService
{
    /*
    |--------------------------------------------------------------------------
    | INTERVALO ENTRE VIAJES
    |--------------------------------------------------------------------------
    */

    private const INTERVALO_VIAJE_MINUTOS = 30;

    public function __construct(
        private readonly QrService $qrService,
        private readonly ChoferContextService $choferContext,
        private readonly ArqueoService $arqueoService,
    ) {
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS DE AISLAMIENTO
    // ─────────────────────────────────────────────────────────────

    private function validarPropietarioViaje(
        Viaje $viaje
    ): void {
        $idChofer = $this->choferContext
            ->idChoferActual();

        if ($idChofer === null) {
            return;
        }

        $idChoferDelViaje = $viaje
            ->vehiculoChoferRuta
            ?->asignacion
            ?->id_chofer;

        if (
            (int) $idChoferDelViaje !==
            $idChofer
        ) {
            throw new AccessDeniedHttpException(
                'No tienes acceso a este viaje.'
            );
        }
    }

    private function validarPropietarioVenta(
        Venta $venta
    ): void {
        $idChofer = $this->choferContext
            ->idChoferActual();

        if ($idChofer === null) {
            return;
        }

        $idChoferDelViaje = $venta
            ->viaje
            ?->vehiculoChoferRuta
            ?->asignacion
            ?->id_chofer;

        if (
            (int) $idChoferDelViaje !==
            $idChofer
        ) {
            throw new AccessDeniedHttpException(
                'No tienes acceso a esta venta.'
            );
        }
    }

    // ─────────────────────────────────────────────────────────────
    // VIAJES
    // ─────────────────────────────────────────────────────────────

    public function listarViajes(
        array $filtros,
        int $perPage = 15
    ) {
        $idChofer = $this->choferContext
            ->idChoferActual();

        $query = Viaje::query()
            ->with([
                'vehiculoChoferRuta.ruta',
                'vehiculoChoferRuta.asignacion.vehiculo',
                'vehiculoChoferRuta.asignacion.chofer.usuario',
            ])
            ->orderByRaw(
                "CASE estado
                    WHEN 'Vendiendo' THEN 0
                    WHEN 'En curso' THEN 1
                    ELSE 2
                END"
            )
            ->orderBy(
                'created_at',
                'desc'
            );

        if ($idChofer !== null) {
            $query->whereHas(
                'vehiculoChoferRuta.asignacion',
                fn($q) =>
                    $q->where(
                        'id_chofer',
                        $idChofer
                    )
            );
        }

        if (!empty($filtros['origen'])) {
            $query->whereHas(
                'vehiculoChoferRuta.ruta',
                fn($q) =>
                    $q->where(
                        'origen',
                        'like',
                        '%' .
                        $filtros['origen'] .
                        '%'
                    )
            );
        }

        if (!empty($filtros['destino'])) {
            $query->whereHas(
                'vehiculoChoferRuta.ruta',
                fn($q) =>
                    $q->where(
                        'destino',
                        'like',
                        '%' .
                        $filtros['destino'] .
                        '%'
                    )
            );
        }

        if (!empty($filtros['fecha'])) {
            $query->whereHas(
                'vehiculoChoferRuta',
                fn($q) =>
                    $q->whereDate(
                        'hora_inicio',
                        $filtros['fecha']
                    )
            );
        }

        if (!empty($filtros['estado'])) {
            $query->where(
                'estado',
                $filtros['estado']
            );
        }

        if (!empty($filtros['vehiculo_id'])) {
            $query->whereHas(
                'vehiculoChoferRuta.asignacion',
                fn($q) =>
                    $q->where(
                        'id_vehiculo',
                        $filtros['vehiculo_id']
                    )
            );
        }

        if (!empty($filtros['chofer_id'])) {
            $query->whereHas(
                'vehiculoChoferRuta.asignacion',
                fn($q) =>
                    $q->where(
                        'id_chofer',
                        $filtros['chofer_id']
                    )
            );
        }

        return $query->paginate(
            $perPage
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRÓXIMA HORA DE UNA RUTA
    |--------------------------------------------------------------------------
    */

    public function obtenerProximaHoraRuta(
        int $idRuta
    ): array {
        $ruta = Ruta::query()
            ->findOrFail(
                $idRuta
            );

        if (
            mb_strtolower(
                trim(
                    (string) $ruta->estado
                )
            ) !== 'activa'
        ) {
            throw new RuntimeException(
                'La ruta seleccionada no está activa.'
            );
        }

        $proximaHora =
            $this->calcularProximaHoraRuta(
                $ruta
            );

        return [
            'id_ruta' =>
                (int) $ruta->id,

            'fecha' =>
                $proximaHora
                    ->format('Y-m-d'),

            'hora' =>
                $proximaHora
                    ->format('H:i'),

            'fecha_hora' =>
                $proximaHora
                    ->format('Y-m-d H:i:s'),

            'intervalo_minutos' =>
                self::INTERVALO_VIAJE_MINUTOS,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR VIAJE PROGRAMADO
    |--------------------------------------------------------------------------
    */

    public function crearViajeProgramado(
        int $idAsignacion,
        int $idRuta
    ): Viaje {
        return DB::transaction(
            function () use (
                $idAsignacion,
                $idRuta
            ): Viaje {
                $ruta = Ruta::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $idRuta
                    );

                if (
                    mb_strtolower(
                        trim(
                            (string) $ruta->estado
                        )
                    ) !== 'activa'
                ) {
                    throw new RuntimeException(
                        'La ruta seleccionada no está activa.'
                    );
                }

                $asignacion =
                    AsignacionVehiculoChofer::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $idAsignacion
                        );

                if (
                    mb_strtolower(
                        trim(
                            (string)
                            $asignacion->estado
                        )
                    ) !== 'activo'
                ) {
                    throw new RuntimeException(
                        'La asignación vehículo-chofer seleccionada no está activa.'
                    );
                }

                $this->choferContext
                    ->validarPertenece(
                        (int)
                        $asignacion
                            ->id_chofer
                    );

                $proximaHora =
                    $this->calcularProximaHoraRuta(
                        $ruta
                    );

                $vehiculoChoferRuta =
                    VehiculoChoferRuta::query()
                        ->firstOrCreate(
                            [
                                'id_asignacion_vehiculo_chofer' =>
                                    $idAsignacion,

                                'id_ruta' =>
                                    $idRuta,

                                'hora_inicio' =>
                                    $proximaHora
                                        ->format(
                                            'Y-m-d H:i:s'
                                        ),
                            ]
                        );

                $viajeExistente =
                    Viaje::query()
                        ->where(
                            'id_vehiculo_chofer_ruta',
                            $vehiculoChoferRuta->id
                        )
                        ->exists();

                if ($viajeExistente) {
                    throw new RuntimeException(
                        'Ya existe un viaje para la hora calculada. Actualiza la pantalla e inténtalo nuevamente.'
                    );
                }

                $viaje =
                    Viaje::query()
                        ->create([
                            'id_vehiculo_chofer_ruta' =>
                                $vehiculoChoferRuta
                                    ->id,

                            'estado' =>
                                'Vendiendo',
                        ]);

                return $viaje
                    ->load([
                        'vehiculoChoferRuta.ruta',
                        'vehiculoChoferRuta.asignacion.vehiculo',
                        'vehiculoChoferRuta.asignacion.chofer.usuario',
                    ]);
            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULAR PRÓXIMA HORA
    |--------------------------------------------------------------------------
    */

    private function calcularProximaHoraRuta(
        Ruta $ruta
    ): Carbon {
        $horaBase =
            trim(
                (string)
                $ruta->hora_inicio
            );

        if ($horaBase === '') {
            throw new RuntimeException(
                'La ruta seleccionada no tiene una hora de inicio configurada.'
            );
        }

        $fechaBase =
            $ruta->fecha_inicio
                ?->format(
                    'Y-m-d'
                )
            ??
            now()->format(
                'Y-m-d'
            );

        $inicioRuta =
            Carbon::parse(
                $fechaBase .
                ' ' .
                $horaBase
            );

        $ultimaHora =
            DB::table(
                'vehiculo_chofer_ruta as vcr'
            )
                ->join(
                    'viaje as v',
                    'v.id_vehiculo_chofer_ruta',
                    '=',
                    'vcr.id'
                )
                ->where(
                    'vcr.id_ruta',
                    $ruta->id
                )
                ->whereNotNull(
                    'vcr.hora_inicio'
                )
                ->max(
                    'vcr.hora_inicio'
                );

        if (!$ultimaHora) {
            return $inicioRuta;
        }

        $proximaHora =
            Carbon::parse(
                (string)
                $ultimaHora
            )
                ->addMinutes(
                    self::INTERVALO_VIAJE_MINUTOS
                );

        if (
            $proximaHora->lt(
                $inicioRuta
            )
        ) {
            return $inicioRuta;
        }

        return $proximaHora;
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR VIAJE LEGACY
    |--------------------------------------------------------------------------
    */

    public function crearViaje(
        int $idVehiculoChoferRuta
    ): Viaje {
        $vehiculoChoferRuta =
            VehiculoChoferRuta::query()
                ->with(
                    'asignacion'
                )
                ->findOrFail(
                    $idVehiculoChoferRuta
                );

        $this->choferContext
            ->validarPertenece(
                (int)
                $vehiculoChoferRuta
                    ->asignacion
                    ?->id_chofer
            );

        $viaje =
            Viaje::query()
                ->create([
                    'id_vehiculo_chofer_ruta' =>
                        $idVehiculoChoferRuta,

                    'estado' =>
                        'Vendiendo',
                ]);

        return $viaje->load([
            'vehiculoChoferRuta.ruta',
            'vehiculoChoferRuta.asignacion.vehiculo',
            'vehiculoChoferRuta.asignacion.chofer.usuario',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SINCRONIZAR ESTADO DE ENCOMIENDAS
    |--------------------------------------------------------------------------
    |
    | Viaje             Encomienda
    |
    | En curso       -> En tránsito
    | Finalizado     -> En destino
    |
    | Vendiendo:
    | No es necesario modificar porque las encomiendas nuevas
    | comienzan en "En origen".
    |
    | Cancelado:
    | No modifica automáticamente las encomiendas.
    |
    | IMPORTANTE:
    | Nunca modifica encomiendas Entregadas o Anuladas.
    |
    */

    private function sincronizarEstadoEncomiendas(
        Viaje $viaje,
        string $estadoViaje
    ): void {
        $estadoEncomienda =
            match ($estadoViaje) {
                'En curso' =>
                    'En tránsito',

                'Finalizado' =>
                    'En destino',

                default =>
                    null,
            };

        if ($estadoEncomienda === null) {
            return;
        }

        Encomienda::query()
            ->whereIn(
                'id',
                function ($query) use (
                    $viaje
                ): void {
                    $query
                        ->select(
                            'id_encomienda'
                        )
                        ->from(
                            'viaje_encomienda'
                        )
                        ->where(
                            'id_viaje',
                            $viaje->id
                        );
                }
            )
            ->whereNotIn(
                'estado',
                [
                    'Entregada',
                    'Anulada',
                ]
            )
            ->update([
                'estado' =>
                    $estadoEncomienda,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CAMBIAR ESTADO DEL VIAJE
    |--------------------------------------------------------------------------
    */

    public function actualizarEstadoViaje(
        Viaje $viaje,
        string $estado
    ): Viaje {
        return DB::transaction(
            function () use (
                $viaje,
                $estado
            ): Viaje {
                $viaje->loadMissing(
                    'vehiculoChoferRuta.asignacion'
                );

                $this->validarPropietarioViaje(
                    $viaje
                );

                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR VIAJE
                |--------------------------------------------------------------------------
                */

                $viaje->update([
                    'estado' =>
                        $estado,
                ]);

                /*
                |--------------------------------------------------------------------------
                | SINCRONIZAR ENCOMIENDAS DEL VIAJE
                |--------------------------------------------------------------------------
                */

                $this->sincronizarEstadoEncomiendas(
                    $viaje,
                    $estado
                );

                return $viaje->fresh();
            }
        );
    }

    // ─────────────────────────────────────────────────────────────
    // ASIENTOS
    // ─────────────────────────────────────────────────────────────

    public function obtenerAsientos(
        int $idViaje
    ): array {
        $viaje =
            Viaje::query()
                ->with([
                    'vehiculoChoferRuta.asignacion.vehiculo.pisos.asientos',
                    'vehiculoChoferRuta.asignacion',
                ])
                ->findOrFail(
                    $idViaje
                );

        $this->validarPropietarioViaje(
            $viaje
        );

        $vehiculo =
            $viaje
                ->vehiculoChoferRuta
                ->asignacion
                ->vehiculo;

        $detallesActivos =
            DetalleVenta::query()
                ->join(
                    'venta',
                    'venta.id',
                    '=',
                    'detalle_venta.id_venta'
                )
                ->where(
                    'venta.id_viaje',
                    $idViaje
                )
                ->whereIn(
                    'venta.estado',
                    [
                        'Pendiente',
                        'Pagada',
                    ]
                )
                ->whereNull(
                    'venta.deleted_at'
                )
                ->select(
                    'detalle_venta.id_asiento',
                    'detalle_venta.id as id_detalle_venta',
                    'venta.id as id_venta',
                    'venta.estado as estado_venta'
                )
                ->get();

        $ocupaciones = [];

        foreach (
            $detallesActivos
            as $detalle
        ) {
            $ocupaciones[
                $detalle->id_asiento
            ] = [
                'id_venta' =>
                    $detalle
                        ->id_venta,

                'id_detalle_venta' =>
                    $detalle
                        ->id_detalle_venta,

                'estado_venta' =>
                    $detalle
                        ->estado_venta,
            ];
        }

        $resultado = [];

        foreach (
            $vehiculo->pisos
            as $piso
        ) {
            $pisoData = [
                'id' =>
                    $piso->id,

                'nombre' =>
                    $piso->nombre,

                'numero' =>
                    $piso->numero,

                'asientos' =>
                    [],
            ];

            foreach (
                $piso->asientos
                as $asiento
            ) {
                $estadoOcupacion =
                    'libre';

                $idVenta = null;
                $idDetalleVenta = null;

                if (
                    $asiento->tipo_celda !==
                    'pasajero'
                ) {
                    $estadoOcupacion =
                        'no_disponible';
                } elseif (
                    isset(
                        $ocupaciones[
                            $asiento->id
                        ]
                    )
                ) {
                    $estadoOcupacion =
                        $ocupaciones[
                            $asiento->id
                        ][
                            'estado_venta'
                        ] ===
                        'Pendiente'
                            ? 'reservado'
                            : 'vendido';

                    $idVenta =
                        $ocupaciones[
                            $asiento->id
                        ][
                            'id_venta'
                        ];

                    $idDetalleVenta =
                        $ocupaciones[
                            $asiento->id
                        ][
                            'id_detalle_venta'
                        ];
                }

                $pisoData[
                    'asientos'
                ][] = [
                    'id' =>
                        $asiento->id,

                    'fila' =>
                        $asiento->fila,

                    'columna' =>
                        $asiento->columna,

                    'tipo_celda' =>
                        $asiento->tipo_celda,

                    'numero_asiento' =>
                        $asiento->numero_asiento,

                    'estado' =>
                        $asiento->estado,

                    'estado_ocupacion' =>
                        $estadoOcupacion,

                    'id_venta' =>
                        $idVenta,

                    'id_detalle_venta' =>
                        $idDetalleVenta,
                ];
            }

            $resultado[] =
                $pisoData;
        }

        return $resultado;
    }

    // ─────────────────────────────────────────────────────────────
    // VENTAS
    // ─────────────────────────────────────────────────────────────

    public function iniciarVenta(
        int $idViaje,
        array $asientos,
        int $userId
    ): Venta {
        $viaje =
            Viaje::query()
                ->with(
                    'vehiculoChoferRuta.asignacion'
                )
                ->findOrFail(
                    $idViaje
                );

        $this->validarPropietarioViaje(
            $viaje
        );

        if (
            $viaje->estado !==
            'Vendiendo'
        ) {
            throw new RuntimeException(
                'El viaje no está en estado Vendiendo.'
            );
        }

        return DB::transaction(
            function () use (
                $idViaje,
                $asientos,
                $userId
            ) {
                foreach (
                    $asientos
                    as $asientoData
                ) {
                    $asientoId =
                        $asientoData[
                            'id_asiento'
                        ];

                    $ocupado =
                        DetalleVenta::query()
                            ->join(
                                'venta',
                                'venta.id',
                                '=',
                                'detalle_venta.id_venta'
                            )
                            ->where(
                                'venta.id_viaje',
                                $idViaje
                            )
                            ->whereIn(
                                'venta.estado',
                                [
                                    'Pendiente',
                                    'Pagada',
                                ]
                            )
                            ->whereNull(
                                'venta.deleted_at'
                            )
                            ->where(
                                'detalle_venta.id_asiento',
                                $asientoId
                            )
                            ->exists();

                    if ($ocupado) {
                        throw new RuntimeException(
                            "El asiento {$asientoId} ya está ocupado."
                        );
                    }
                }

                $venta =
                    Venta::query()
                        ->create([
                            'id_viaje' =>
                                $idViaje,

                            'id_user' =>
                                $userId,

                            'estado' =>
                                'Pendiente',

                            'precio_total' =>
                                0,
                        ]);

                $precioTotal = 0;

                foreach (
                    $asientos
                    as $asientoData
                ) {
                    $precio =
                        (float)
                        $asientoData[
                            'precio_unitario'
                        ];

                    $venta
                        ->detalles()
                        ->create([
                            'id_asiento' =>
                                $asientoData[
                                    'id_asiento'
                                ],

                            'precio_unitario' =>
                                $precio,
                        ]);

                    $precioTotal +=
                        $precio;
                }

                $venta->update([
                    'precio_total' =>
                        $precioTotal,
                ]);

                return $venta->load([
                    'detalles.asiento',
                    'detalles.pasajero',
                ]);
            }
        );
    }

    public function asignarPasajero(
        int $detalleId,
        array $datosPasajero
    ): DetalleVenta {
        $detalle =
            DetalleVenta::query()
                ->findOrFail(
                    $detalleId
                );

        DB::transaction(
            function () use (
                $detalle,
                $datosPasajero
            ) {
                if (
                    $detalle->id_pasajero
                ) {
                    $pasajero =
                        Pasajero::query()
                            ->findOrFail(
                                $detalle
                                    ->id_pasajero
                            );

                    $pasajero->update(
                        $datosPasajero
                    );
                } else {
                    $pasajero =
                        Pasajero::query()
                            ->create(
                                $datosPasajero
                            );

                    $detalle->update([
                        'id_pasajero' =>
                            $pasajero->id,
                    ]);
                }
            }
        );

        return $detalle->fresh(
            'pasajero'
        );
    }

    public function confirmarVenta(
        int $ventaId,
        string $formaPago,
        array $pasajeros
    ): Venta {
        $venta =
            Venta::query()
                ->with([
                    'detalles',
                    'viaje.vehiculoChoferRuta.asignacion',
                ])
                ->findOrFail(
                    $ventaId
                );

        $this->validarPropietarioVenta(
            $venta
        );

        if (
            $venta->estado !==
            'Pendiente'
        ) {
            throw new RuntimeException(
                'Solo se puede confirmar una venta pendiente.'
            );
        }

        $tipoPago =
            $this->normalizarFormaPago(
                $formaPago
            );

        DB::transaction(
            function () use (
                $venta,
                $formaPago,
                $tipoPago,
                $pasajeros
            ) {
                $detallesVenta =
                    $venta
                        ->detalles
                        ->keyBy(
                            'id'
                        );

                foreach (
                    $pasajeros
                    as $datosPasajero
                ) {
                    if (
                        !$detallesVenta->has(
                            $datosPasajero[
                                'id_detalle_venta'
                            ]
                        )
                    ) {
                        throw new RuntimeException(
                            "El detalle {$datosPasajero['id_detalle_venta']} no pertenece a esta venta."
                        );
                    }
                }

                foreach (
                    $pasajeros
                    as $datosPasajero
                ) {
                    $detalle =
                        $detallesVenta->get(
                            $datosPasajero[
                                'id_detalle_venta'
                            ]
                        );

                    $pasajeroData = [
                        'nombres' =>
                            $datosPasajero[
                                'nombres'
                            ],

                        'apellido_paterno' =>
                            $datosPasajero[
                                'apellido_paterno'
                            ],

                        'apellido_materno' =>
                            $datosPasajero[
                                'apellido_materno'
                            ]
                            ?? null,

                        'ci' =>
                            $datosPasajero[
                                'ci'
                            ]
                            ?? null,
                    ];

                    if (
                        $detalle->id_pasajero
                    ) {
                        $pasajero =
                            Pasajero::query()
                                ->findOrFail(
                                    $detalle
                                        ->id_pasajero
                                );

                        $pasajero->update(
                            $pasajeroData
                        );
                    } else {
                        $pasajero =
                            Pasajero::query()
                                ->create(
                                    $pasajeroData
                                );
                    }

                    $precioUnitario =
                        isset(
                            $datosPasajero[
                                'precio_unitario'
                            ]
                        )
                            ? (float)
                            $datosPasajero[
                                'precio_unitario'
                            ]
                            : (float)
                            $detalle
                                ->precio_unitario;

                    $detalle->update([
                        'id_pasajero' =>
                            $pasajero->id,

                        'precio_unitario' =>
                            $precioUnitario,
                    ]);
                }

                $venta->update([
                    'estado' =>
                        'Pagada',

                    'forma_pago' =>
                        $formaPago,

                    'precio_total' =>
                        $venta
                            ->detalles()
                            ->sum(
                                'precio_unitario'
                            ),
                ]);

                $this->arqueoService
                    ->registrarIngreso(
                        idUser:
                            (int)
                            $venta->id_user,

                        tipoTransaccion:
                            'VPASAJE',

                        monto:
                            (float)
                            $venta->precio_total,

                        tipoPago:
                            $tipoPago,

                        detalle:
                            $this
                                ->construirDetalleIngreso(
                                    $venta
                                ),

                        nombreTipoTransaccion:
                            'Venta de pasaje',
                    );
            }
        );

        return $venta
            ->fresh()
            ->load([
                'detalles.asiento',
                'detalles.pasajero',
                'viaje.vehiculoChoferRuta.ruta',
            ]);
    }

    public function cancelarVenta(
        int $ventaId
    ): void {
        $venta =
            Venta::query()
                ->with(
                    'viaje.vehiculoChoferRuta.asignacion'
                )
                ->findOrFail(
                    $ventaId
                );

        $this->validarPropietarioVenta(
            $venta
        );

        if (
            $venta->estado !==
            'Pendiente'
        ) {
            throw new RuntimeException(
                'Solo se puede cancelar una venta pendiente.'
            );
        }

        DB::transaction(
            function () use (
                $venta
            ) {
                $venta
                    ->detalles()
                    ->delete();

                $venta->update([
                    'estado' =>
                        'Anulada',
                ]);
            }
        );
    }

    public function anularVenta(
        int $ventaId
    ): void {
        $venta =
            Venta::query()
                ->with(
                    'viaje.vehiculoChoferRuta.asignacion'
                )
                ->findOrFail(
                    $ventaId
                );

        $this->validarPropietarioVenta(
            $venta
        );

        if (
            $venta->estado ===
            'Anulada'
        ) {
            throw new RuntimeException(
                'La venta ya está anulada.'
            );
        }

        DB::transaction(
            function () use (
                $venta
            ) {
                $this
                    ->anularIngresoDeVenta(
                        $venta
                    );

                $venta
                    ->detalles()
                    ->delete();

                $venta->update([
                    'estado' =>
                        'Anulada',
                ]);

                $venta->delete();
            }
        );
    }

    public function eliminarDetalle(
        int $detalleId
    ): void {
        $detalle =
            DetalleVenta::query()
                ->with(
                    'venta.viaje.vehiculoChoferRuta.asignacion'
                )
                ->findOrFail(
                    $detalleId
                );

        $venta =
            $detalle->venta;

        $this->validarPropietarioVenta(
            $venta
        );

        if (
            $venta->estado ===
            'Anulada'
        ) {
            throw new RuntimeException(
                'La venta ya está anulada.'
            );
        }

        DB::transaction(
            function () use (
                $detalle,
                $venta
            ) {
                $detalle->delete();

                $restantes =
                    $venta
                        ->detalles()
                        ->count();

                if ($restantes === 0) {
                    $venta->update([
                        'estado' =>
                            'Anulada',
                    ]);

                    $venta->delete();
                } else {
                    $venta->update([
                        'precio_total' =>
                            $venta
                                ->detalles()
                                ->sum(
                                    'precio_unitario'
                                ),
                    ]);
                }
            }
        );
    }

    public function cambiarAsiento(
        int $detalleId,
        int $nuevoAsientoId
    ): DetalleVenta {
        $detalle =
            DetalleVenta::query()
                ->with(
                    'venta.viaje.vehiculoChoferRuta.asignacion'
                )
                ->findOrFail(
                    $detalleId
                );

        $venta =
            $detalle->venta;

        $this->validarPropietarioVenta(
            $venta
        );

        if (
            $venta->estado ===
            'Anulada'
        ) {
            throw new RuntimeException(
                'No se puede cambiar asiento en una venta anulada.'
            );
        }

        $ocupado =
            DetalleVenta::query()
                ->join(
                    'venta',
                    'venta.id',
                    '=',
                    'detalle_venta.id_venta'
                )
                ->where(
                    'venta.id_viaje',
                    $venta->id_viaje
                )
                ->whereIn(
                    'venta.estado',
                    [
                        'Pendiente',
                        'Pagada',
                    ]
                )
                ->whereNull(
                    'venta.deleted_at'
                )
                ->where(
                    'detalle_venta.id_asiento',
                    $nuevoAsientoId
                )
                ->where(
                    'detalle_venta.id',
                    '!=',
                    $detalle->id
                )
                ->exists();

        if ($ocupado) {
            throw new RuntimeException(
                'El asiento seleccionado ya está ocupado.'
            );
        }

        $detalle->update([
            'id_asiento' =>
                $nuevoAsientoId,
        ]);

        return $detalle->fresh(
            'asiento'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // REIMPRESIÓN / PDF
    // ─────────────────────────────────────────────────────────────

    public function obtenerVenta(
        int $ventaId
    ): Venta {
        $venta =
            Venta::query()
                ->with([
                    'detalles.pasajero',
                    'detalles.asiento',
                    'viaje.vehiculoChoferRuta.ruta',
                    'viaje.vehiculoChoferRuta.asignacion.vehiculo',
                    'viaje.vehiculoChoferRuta.asignacion.chofer.usuario',
                    'viaje.vehiculoChoferRuta.asignacion',
                ])
                ->findOrFail(
                    $ventaId
                );

        $this->validarPropietarioVenta(
            $venta
        );

        return $venta;
    }

    public function generarPdfVenta(
        int $ventaId
    ): \Barryvdh\DomPDF\PDF {
        $venta =
            $this->obtenerVenta(
                $ventaId
            );

        $qrData =
            $this->qrService
                ->generateQrImage(
                    $ventaId
                );

        return Pdf::loadView(
            'pasajes.ticket',
            [
                'venta' =>
                    $venta,

                'qrData' =>
                    $qrData,
            ]
        );
    }

    public function generarQrData(
        int $ventaId
    ): string {
        return $this->qrService
            ->generateQrImage(
                $ventaId
            );
    }

    // ─────────────────────────────────────────────────────────────
    // INTEGRACIÓN CON ARQUEO
    // ─────────────────────────────────────────────────────────────

    private function construirDetalleIngreso(
        Venta $venta
    ): string {
        return sprintf(
            'Venta #%d - Viaje #%d',
            $venta->id,
            $venta->id_viaje
        );
    }

    private function anularIngresoDeVenta(
        Venta $venta
    ): void {
        $ingreso =
            Ingreso::query()
                ->where(
                    'id_user',
                    $venta->id_user
                )
                ->where(
                    'detalle',
                    $this->construirDetalleIngreso(
                        $venta
                    )
                )
                ->where(
                    'estado',
                    'Valido'
                )
                ->latest(
                    'id'
                )
                ->first();

        if (!$ingreso) {
            return;
        }

        $this->arqueoService
            ->anularIngreso(
                $ingreso
            );
    }

    private function normalizarFormaPago(
        string $formaPago
    ): string {
        $clave =
            mb_strtolower(
                trim(
                    $formaPago
                )
            );

        $mapa = [
            'efectivo' =>
                'Efectivo',

            'cash' =>
                'Efectivo',

            'tarjeta' =>
                'Tarjeta',

            'tarjeta de credito' =>
                'Tarjeta',

            'tarjeta de crédito' =>
                'Tarjeta',

            'tarjeta de debito' =>
                'Tarjeta',

            'tarjeta de débito' =>
                'Tarjeta',

            'credito' =>
                'Tarjeta',

            'crédito' =>
                'Tarjeta',

            'debito' =>
                'Tarjeta',

            'débito' =>
                'Tarjeta',

            'card' =>
                'Tarjeta',

            'qr' =>
                'QR',

            'transferencia' =>
                'Transferencia',

            'transferencia bancaria' =>
                'Transferencia',

            'transfer' =>
                'Transferencia',
        ];

        if (
            isset(
                $mapa[
                    $clave
                ]
            )
        ) {
            return $mapa[
                $clave
            ];
        }

        throw new RuntimeException(
            "Forma de pago '{$formaPago}' no soportada por el módulo de arqueo. " .
            'Use uno de: Efectivo, Tarjeta, QR, Transferencia.'
        );
    }
}