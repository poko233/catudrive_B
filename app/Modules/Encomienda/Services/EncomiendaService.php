<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Services;

use App\Shared\Models\AsignacionVehiculoChofer;
use App\Shared\Models\Encomienda;
use App\Shared\Models\Ruta;
use App\Shared\Models\VehiculoChoferRuta;
use App\Shared\Models\VehiculoChoferRutaEncomienda;
use App\Shared\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EncomiendaService
{
    public function __construct(
        private readonly AuditService $audit
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BASE
    |--------------------------------------------------------------------------
    */

    private function queryBase(): Builder
    {
        return Encomienda::query()
            ->with([
                'asignacionViaje.viaje.ruta',
                'asignacionViaje.viaje.asignacion.chofer.usuario',
                'asignacionViaje.viaje.asignacion.vehiculo',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function listar(): Collection
    {
        return $this
            ->queryBase()
            ->orderByDesc(
                'id'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | OBTENER
    |--------------------------------------------------------------------------
    */

    public function obtener(
        int $id
    ): Encomienda {
        return $this
            ->queryBase()
            ->findOrFail(
                $id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | BUSCAR POR GUÍA
    |--------------------------------------------------------------------------
    */

    public function buscarPorGuia(
        string $guia
    ): Encomienda {
        $guia =
            mb_strtoupper(
                trim(
                    $guia
                )
            );

        return $this
            ->queryBase()
            ->where(
                'guia',
                $guia
            )
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | CATÁLOGOS
    |--------------------------------------------------------------------------
    */

    public function catalogos(): array
    {
        /*
        |--------------------------------------------------------------------------
        | ASIGNACIONES ACTIVAS
        |--------------------------------------------------------------------------
        */

        $asignaciones =
            AsignacionVehiculoChofer::query()
                ->with([
                    'chofer.usuario',
                    'vehiculo',
                ])
                ->where(
                    'estado',
                    'Activo'
                )
                ->orderByDesc(
                    'fecha_asignacion'
                )
                ->get()
                ->map(
                    function (
                        AsignacionVehiculoChofer $asignacion
                    ): array {
                        $chofer =
                            $asignacion->chofer;

                        $usuario =
                            $chofer
                                ?->usuario;

                        $vehiculo =
                            $asignacion->vehiculo;

                        $nombre =
                            trim(
                                implode(
                                    ' ',
                                    array_filter([
                                        $usuario?->nombres,
                                        $usuario?->primer_apellido,
                                        $usuario?->segundo_apellido,
                                    ])
                                )
                            );

                        return [
                            'id' =>
                                (int)
                                $asignacion->id,

                            'fecha_asignacion' =>
                                $asignacion
                                    ->fecha_asignacion
                                    ?->format(
                                        'Y-m-d'
                                    ),

                            'chofer' => [
                                'id' =>
                                    (int)
                                    $asignacion->id_chofer,

                                'nombre' =>
                                    $nombre,

                                'ci' =>
                                    $usuario?->ci,

                                'carnet_sindical' =>
                                    $chofer
                                        ?->carnet_sindical,
                            ],

                            'vehiculo' => [
                                'id' =>
                                    (int)
                                    $asignacion->id_vehiculo,

                                'placa' =>
                                    $vehiculo
                                        ?->placa,

                                'tipo' =>
                                    $vehiculo
                                        ?->tipo,

                                'marca' =>
                                    $vehiculo
                                        ?->marca,

                                'modelo' =>
                                    $vehiculo
                                        ?->modelo,

                                'color' =>
                                    $vehiculo
                                        ?->color,

                                'estado' =>
                                    $vehiculo
                                        ?->estado,
                            ],
                        ];
                    }
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | RUTAS ACTIVAS
        |--------------------------------------------------------------------------
        */

        $rutas =
            Ruta::query()
                ->where(
                    'estado',
                    'Activa'
                )
                ->orderBy(
                    'origen'
                )
                ->orderBy(
                    'destino'
                )
                ->get()
                ->map(
                    fn (Ruta $ruta): array => [
                        'id' =>
                            (int)
                            $ruta->id,

                        'origen' =>
                            $ruta->origen,

                        'destino' =>
                            $ruta->destino,

                        'estado' =>
                            $ruta->estado,
                    ]
                )
                ->values();

        return [
            'asignaciones' =>
                $asignaciones,

            'rutas' =>
                $rutas,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $data
    ): Encomienda {
        $id =
            0;

        DB::transaction(
            function () use (
                $data,
                &$id
            ): void {
                /*
                |--------------------------------------------------------------------------
                | CREAR ENCOMIENDA
                |--------------------------------------------------------------------------
                |
                | La guía se genera posteriormente usando el ID.
                |
                */

                $encomienda =
                    Encomienda::query()
                        ->create([
                            'guia' =>
                                null,

                            'remitente' =>
                                $data[
                                    'remitente'
                                ],

                            'destinatario' =>
                                $data[
                                    'destinatario'
                                ],

                            'origen' =>
                                $data[
                                    'origen'
                                ],

                            'destino' =>
                                $data[
                                    'destino'
                                ],

                            'descripcion' =>
                                $data[
                                    'descripcion'
                                ] ?? null,

                            'cantidad' =>
                                (int)
                                $data[
                                    'cantidad'
                                ],

                            'precio' =>
                                $data[
                                    'precio'
                                ],

                            'estado' =>
                                'Registrada',
                        ]);

                $id =
                    (int)
                    $encomienda->id;

                /*
                |--------------------------------------------------------------------------
                | GENERAR GUÍA CORRELATIVA
                |--------------------------------------------------------------------------
                */

                $encomienda->guia =
                    $this->generarGuia(
                        $id
                    );

                $encomienda->save();
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | AUDITORÍA
        |--------------------------------------------------------------------------
        */

        $this->audit
            ->created(
                resource:
                    'Encomienda',

                resourceId:
                    $id,

                after:
                    $this->snapshot(
                        $fresh
                    ),
            );

        return $fresh;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR
    |--------------------------------------------------------------------------
    */

    public function actualizar(
        int $id,
        array $data
    ): Encomienda {
        $before =
            [];

        DB::transaction(
            function () use (
                $id,
                $data,
                &$before
            ): void {
                /** @var Encomienda $encomienda */
                $encomienda =
                    Encomienda::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                /*
                |--------------------------------------------------------------------------
                | SOLO REGISTRADA
                |--------------------------------------------------------------------------
                */

                if (
                    !$encomienda
                        ->estaRegistrada()
                ) {
                    throw ValidationException::withMessages([
                        'encomienda' =>
                            'Solo se puede editar una encomienda registrada.',
                    ]);
                }

                $before =
                    $this->snapshot(
                        $encomienda
                    );

                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR
                |--------------------------------------------------------------------------
                */

                $encomienda->remitente =
                    $data[
                        'remitente'
                    ];

                $encomienda->destinatario =
                    $data[
                        'destinatario'
                    ];

                $encomienda->origen =
                    $data[
                        'origen'
                    ];

                $encomienda->destino =
                    $data[
                        'destino'
                    ];

                $encomienda->descripcion =
                    $data[
                        'descripcion'
                    ] ?? null;

                $encomienda->cantidad =
                    (int)
                    $data[
                        'cantidad'
                    ];

                $encomienda->precio =
                    $data[
                        'precio'
                    ];

                $encomienda->save();
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | AUDITORÍA
        |--------------------------------------------------------------------------
        */

        $this->audit
            ->updated(
                resource:
                    'Encomienda',

                resourceId:
                    $id,

                before:
                    $before,

                after:
                    $this->snapshot(
                        $fresh
                    ),
            );

        return $fresh;
    }

    /*
    |--------------------------------------------------------------------------
    | ASIGNAR A VIAJE
    |--------------------------------------------------------------------------
    */

    public function asignar(
        int $id,
        array $data
    ): Encomienda {
        $before =
            [];

        DB::transaction(
            function () use (
                $id,
                $data,
                &$before
            ): void {
                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR ENCOMIENDA
                |--------------------------------------------------------------------------
                */

                /** @var Encomienda $encomienda */
                $encomienda =
                    Encomienda::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                if (
                    !$encomienda
                        ->estaRegistrada()
                ) {
                    throw ValidationException::withMessages([
                        'encomienda' =>
                            'Solo se puede asignar una encomienda registrada.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | VERIFICAR QUE NO ESTÉ ASIGNADA
                |--------------------------------------------------------------------------
                */

                $yaAsignada =
                    VehiculoChoferRutaEncomienda::query()
                        ->where(
                            'id_encomienda',
                            $encomienda->id
                        )
                        ->exists();

                if (
                    $yaAsignada
                ) {
                    throw ValidationException::withMessages([
                        'encomienda' =>
                            'La encomienda ya se encuentra asignada a un viaje.',
                    ]);
                }

                $idAsignacion =
                    (int)
                    $data[
                        'id_asignacion_vehiculo_chofer'
                    ];

                $idRuta =
                    (int)
                    $data[
                        'id_ruta'
                    ];

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR ASIGNACIÓN
                |--------------------------------------------------------------------------
                |
                | Esto también serializa la creación de viajes para
                | una misma asignación Chofer + Vehículo.
                |
                */

                /** @var AsignacionVehiculoChofer $asignacion */
                $asignacion =
                    AsignacionVehiculoChofer::query()
                        ->with([
                            'chofer.usuario',
                            'vehiculo',
                        ])
                        ->lockForUpdate()
                        ->findOrFail(
                            $idAsignacion
                        );

                /*
                |--------------------------------------------------------------------------
                | ASIGNACIÓN ACTIVA
                |--------------------------------------------------------------------------
                */

                if (
                    !$asignacion
                        ->estaActiva()
                ) {
                    throw ValidationException::withMessages([
                        'id_asignacion_vehiculo_chofer' =>
                            'La asignación de chofer y vehículo ya no se encuentra activa.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | VEHÍCULO OPERATIVO
                |--------------------------------------------------------------------------
                */

                if (
                    !$asignacion->vehiculo ||
                    mb_strtoupper(
                        trim(
                            (string)
                            $asignacion
                                ->vehiculo
                                ->estado
                        )
                    ) !== 'OPERATIVO'
                ) {
                    throw ValidationException::withMessages([
                        'id_asignacion_vehiculo_chofer' =>
                            'El vehículo de la asignación no se encuentra operativo.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | CHOFER ACTIVO
                |--------------------------------------------------------------------------
                */

                if (
                    !$asignacion->chofer ||
                    !$asignacion
                        ->chofer
                        ->usuario ||
                    mb_strtoupper(
                        trim(
                            (string)
                            $asignacion
                                ->chofer
                                ->usuario
                                ->estado
                        )
                    ) !== 'ACTIVO'
                ) {
                    throw ValidationException::withMessages([
                        'id_asignacion_vehiculo_chofer' =>
                            'El chofer de la asignación no se encuentra activo.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR RUTA
                |--------------------------------------------------------------------------
                */

                /** @var Ruta $ruta */
                $ruta =
                    Ruta::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $idRuta
                        );

                /*
                |--------------------------------------------------------------------------
                | RUTA ACTIVA
                |--------------------------------------------------------------------------
                */

                if (
                    mb_strtoupper(
                        trim(
                            (string)
                            $ruta->estado
                        )
                    ) !== 'ACTIVA'
                ) {
                    throw ValidationException::withMessages([
                        'id_ruta' =>
                            'La ruta seleccionada no se encuentra activa.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | VALIDAR ORIGEN Y DESTINO
                |--------------------------------------------------------------------------
                */

                if (
                    !$this->textoIgual(
                        (string)
                        $encomienda->origen,

                        (string)
                        $ruta->origen
                    )
                ) {
                    throw ValidationException::withMessages([
                        'id_ruta' =>
                            'El origen de la ruta no coincide con el origen de la encomienda.',
                    ]);
                }

                if (
                    !$this->textoIgual(
                        (string)
                        $encomienda->destino,

                        (string)
                        $ruta->destino
                    )
                ) {
                    throw ValidationException::withMessages([
                        'id_ruta' =>
                            'El destino de la ruta no coincide con el destino de la encomienda.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | NORMALIZAR HORA
                |--------------------------------------------------------------------------
                */

                $horaInicio =
                    Carbon::parse(
                        (string)
                        $data[
                            'hora_inicio'
                        ]
                    )
                        ->format(
                            'Y-m-d H:i:s'
                        );

                /*
                |--------------------------------------------------------------------------
                | VALIDAR FECHA DE ASIGNACIÓN
                |--------------------------------------------------------------------------
                */

                $fechaAsignacion =
                    $asignacion
                        ->fecha_asignacion
                        ?->format(
                            'Y-m-d'
                        );

                $fechaViaje =
                    Carbon::parse(
                        $horaInicio
                    )
                        ->format(
                            'Y-m-d'
                        );

                if (
                    $fechaAsignacion &&
                    $fechaViaje <
                    $fechaAsignacion
                ) {
                    throw ValidationException::withMessages([
                        'hora_inicio' =>
                            'La salida no puede ser anterior a la fecha de asignación del vehículo y chofer.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | SNAPSHOT ANTERIOR
                |--------------------------------------------------------------------------
                */

                $before =
                    $this->snapshot(
                        $encomienda
                    );

                /*
                |--------------------------------------------------------------------------
                | BUSCAR / CREAR VIAJE
                |--------------------------------------------------------------------------
                |
                | La BD ya posee UNIQUE:
                |
                | id_asignacion_vehiculo_chofer
                | id_ruta
                | hora_inicio
                |
                | Como bloqueamos la asignación, evitamos que dos procesos
                | creen simultáneamente el mismo viaje para ella.
                |
                */

                $viaje =
                    VehiculoChoferRuta::query()
                        ->where(
                            'id_asignacion_vehiculo_chofer',
                            $idAsignacion
                        )
                        ->where(
                            'id_ruta',
                            $idRuta
                        )
                        ->where(
                            'hora_inicio',
                            $horaInicio
                        )
                        ->first();

                if (
                    !$viaje
                ) {
                    $viaje =
                        VehiculoChoferRuta::query()
                            ->create([
                                'id_asignacion_vehiculo_chofer' =>
                                    $idAsignacion,

                                'id_ruta' =>
                                    $idRuta,

                                'hora_inicio' =>
                                    $horaInicio,
                            ]);
                }

                /*
                |--------------------------------------------------------------------------
                | ASIGNAR ENCOMIENDA
                |--------------------------------------------------------------------------
                */

                VehiculoChoferRutaEncomienda::query()
                    ->create([
                        'id_vehiculo_chofer_ruta' =>
                            (int)
                            $viaje->id,

                        'id_encomienda' =>
                            (int)
                            $encomienda->id,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | EN TRÁNSITO
                |--------------------------------------------------------------------------
                */

                $encomienda->estado =
                    'En tránsito';

                $encomienda->save();
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | AUDITORÍA
        |--------------------------------------------------------------------------
        */

        $this->audit
            ->updated(
                resource:
                    'Encomienda',

                resourceId:
                    $id,

                before:
                    $before,

                after:
                    $this->snapshot(
                        $fresh
                    ),
            );

        return $fresh;
    }

    /*
    |--------------------------------------------------------------------------
    | ENTREGAR
    |--------------------------------------------------------------------------
    */

    public function entregar(
        int $id
    ): Encomienda {
        $before =
            [];

        DB::transaction(
            function () use (
                $id,
                &$before
            ): void {
                /** @var Encomienda $encomienda */
                $encomienda =
                    Encomienda::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                /*
                |--------------------------------------------------------------------------
                | SOLO EN TRÁNSITO
                |--------------------------------------------------------------------------
                */

                if (
                    !$encomienda
                        ->estaEnTransito()
                ) {
                    throw ValidationException::withMessages([
                        'encomienda' =>
                            'Solo se puede entregar una encomienda que se encuentre en tránsito.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | DEBE TENER VIAJE
                |--------------------------------------------------------------------------
                */

                $asignada =
                    VehiculoChoferRutaEncomienda::query()
                        ->where(
                            'id_encomienda',
                            $encomienda->id
                        )
                        ->exists();

                if (
                    !$asignada
                ) {
                    throw ValidationException::withMessages([
                        'encomienda' =>
                            'La encomienda no tiene un viaje asignado.',
                    ]);
                }

                $before =
                    $this->snapshot(
                        $encomienda
                    );

                /*
                |--------------------------------------------------------------------------
                | ENTREGAR
                |--------------------------------------------------------------------------
                */

                $encomienda->estado =
                    'Entregada';

                $encomienda->save();
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | AUDITORÍA
        |--------------------------------------------------------------------------
        */

        $this->audit
            ->updated(
                resource:
                    'Encomienda',

                resourceId:
                    $id,

                before:
                    $before,

                after:
                    $this->snapshot(
                        $fresh
                    ),
            );

        return $fresh;
    }

    /*
    |--------------------------------------------------------------------------
    | ANULAR
    |--------------------------------------------------------------------------
    */

    public function anular(
        int $id
    ): Encomienda {
        $before =
            [];

        DB::transaction(
            function () use (
                $id,
                &$before
            ): void {
                /** @var Encomienda $encomienda */
                $encomienda =
                    Encomienda::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                /*
                |--------------------------------------------------------------------------
                | SOLO REGISTRADA
                |--------------------------------------------------------------------------
                */

                if (
                    !$encomienda
                        ->estaRegistrada()
                ) {
                    throw ValidationException::withMessages([
                        'encomienda' =>
                            'Solo se puede anular una encomienda registrada.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | NO DEBE ESTAR ASIGNADA
                |--------------------------------------------------------------------------
                */

                $asignada =
                    VehiculoChoferRutaEncomienda::query()
                        ->where(
                            'id_encomienda',
                            $encomienda->id
                        )
                        ->exists();

                if (
                    $asignada
                ) {
                    throw ValidationException::withMessages([
                        'encomienda' =>
                            'No se puede anular una encomienda que ya fue asignada a un viaje.',
                    ]);
                }

                $before =
                    $this->snapshot(
                        $encomienda
                    );

                /*
                |--------------------------------------------------------------------------
                | ANULAR
                |--------------------------------------------------------------------------
                */

                $encomienda->estado =
                    'Anulada';

                $encomienda->save();
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | AUDITORÍA
        |--------------------------------------------------------------------------
        */

        $this->audit
            ->updated(
                resource:
                    'Encomienda',

                resourceId:
                    $id,

                before:
                    $before,

                after:
                    $this->snapshot(
                        $fresh
                    ),
            );

        return $fresh;
    }

    /*
    |--------------------------------------------------------------------------
    | GENERAR GUÍA
    |--------------------------------------------------------------------------
    */

    private function generarGuia(
        int $id
    ): string {
        return sprintf(
            'ENC-%06d',
            $id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPARAR TEXTO
    |--------------------------------------------------------------------------
    */

    private function textoIgual(
        string $valorA,
        string $valorB
    ): bool {
        return mb_strtoupper(
            trim(
                $valorA
            )
        ) ===
            mb_strtoupper(
                trim(
                    $valorB
                )
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SNAPSHOT
    |--------------------------------------------------------------------------
    */

    private function snapshot(
        Encomienda $encomienda
    ): array {
        $encomienda
            ->loadMissing([
                'asignacionViaje.viaje',
            ]);

        return [
            'id' =>
                (int)
                $encomienda->id,

            'guia' =>
                $encomienda->guia,

            'remitente' =>
                $encomienda->remitente,

            'destinatario' =>
                $encomienda->destinatario,

            'origen' =>
                $encomienda->origen,

            'destino' =>
                $encomienda->destino,

            'descripcion' =>
                $encomienda->descripcion,

            'cantidad' =>
                (int)
                $encomienda->cantidad,

            'precio' =>
                number_format(
                    (float)
                    $encomienda->precio,
                    2,
                    '.',
                    ''
                ),

            'estado' =>
                $encomienda->estado,

            'id_vehiculo_chofer_ruta' =>
                $encomienda
                    ->asignacionViaje
                    ?->id_vehiculo_chofer_ruta,
        ];
    }
}