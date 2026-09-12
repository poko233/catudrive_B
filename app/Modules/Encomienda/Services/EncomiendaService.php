<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Services;

use App\Shared\Models\Encomienda;
use App\Shared\Models\Viaje;
use App\Shared\Models\ViajeEncomienda;
use App\Shared\Models\Ruta;
use App\Shared\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                'ruta',
                'viajeEncomienda.viaje.vehiculoChoferRuta.ruta',
                'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion.chofer.usuario',
                'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion.vehiculo',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function listar(
        array $filtros = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query =
            $this
                ->queryBase();

        $estado =
            trim(
                (string) ($filtros['estado'] ?? '')
            );

        if ($estado !== '') {
            $query
                ->where(
                    'estado',
                    $this->estadoBaseDatos(
                        $estado
                    )
                );
        }

        $buscar =
            trim(
                (string) ($filtros['buscar'] ?? '')
            );

        if ($buscar !== '') {
            $like =
                '%' . $buscar . '%';

            $query
                ->where(
                    function (Builder $subQuery) use ($like, $buscar): void {
                        $subQuery
                            ->where('guia', 'like', $like)
                            ->orWhere('remitente', 'like', $like)
                            ->orWhere('destinatario', 'like', $like)
                            ->orWhere('descripcion', 'like', $like)
                            ->orWhereHas(
                                'ruta',
                                fn (Builder $ruta) =>
                                    $ruta
                                        ->where('origen', 'like', $like)
                                        ->orWhere('destino', 'like', $like)
                            )
                            ->orWhereHas(
                                'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion.vehiculo',
                                fn (Builder $vehiculo) =>
                                    $vehiculo
                                        ->where('placa', 'like', $like)
                            )
                            ->orWhereHas(
                                'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion.chofer.usuario',
                                fn (Builder $usuario) =>
                                    $usuario
                                        ->where('nombres', 'like', $like)
                                        ->orWhere('primer_apellido', 'like', $like)
                                        ->orWhere('segundo_apellido', 'like', $like)
                            );

                        if (is_numeric($buscar)) {
                            $subQuery
                                ->orWhere(
                                    'cantidad',
                                    (int) $buscar
                                )
                                ->orWhere(
                                    'precio',
                                    (float) $buscar
                                );
                        }

                        if (
                            preg_match(
                                '/^\d{4}-\d{2}-\d{2}$/',
                                $buscar
                            ) === 1
                        ) {
                            $subQuery
                                ->orWhereDate(
                                    'created_at',
                                    $buscar
                                );
                        }
                    }
                );
        }

        return $query
            ->orderByDesc('id')
            ->paginate(
                max(1, min($perPage, 100))
            );
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN GENERAL
    |--------------------------------------------------------------------------
    */

    public function resumen(): array
    {
        $conteos =
            Encomienda::query()
                ->selectRaw(
                    'estado, COUNT(*) AS cantidad'
                )
                ->groupBy(
                    'estado'
                )
                ->pluck(
                    'cantidad',
                    'estado'
                );

        $ingresos =
            Encomienda::query()
                ->where(
                    'estado',
                    '!=',
                    'Anulada'
                )
                ->sum('precio');

        return [
            'total' =>
                (int) $conteos->sum(),

            'registradas' =>
                (int) ($conteos['Registrada'] ?? 0),

            'enTransito' =>
                (int) ($conteos['En tránsito'] ?? 0),

            'entregadas' =>
                (int) ($conteos['Entregada'] ?? 0),

            'anuladas' =>
                (int) ($conteos['Anulada'] ?? 0),

            'ingresos' =>
                (float) $ingresos,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADO DE API A BASE DE DATOS
    |--------------------------------------------------------------------------
    */

    private function estadoBaseDatos(
        string $estado
    ): string {
        return match ($estado) {
            'REGISTRADA' =>
                'Registrada',

            'EN_TRANSITO' =>
                'En tránsito',

            'ENTREGADA' =>
                'Entregada',

            'ANULADA' =>
                'Anulada',

            default =>
                $estado,
        };
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
    | BUSCAR POR QR
    |--------------------------------------------------------------------------
    */

    public function buscarPorQr(
        string $token
    ): Encomienda {
        $encomienda =
            $this->queryBase()
                ->where(
                    'qr_token',
                    $token
                )
                ->firstOrFail();

        if (
            !$encomienda
                ->viajeEncomienda
        ) {
            throw ValidationException::withMessages([
                'qr' =>
                    'La encomienda todavía no se encuentra asignada a un viaje.',
            ]);
        }

        if (
            $encomienda
                ->estaAnulada()
        ) {
            throw ValidationException::withMessages([
                'qr' =>
                    'La encomienda escaneada se encuentra anulada.',
            ]);
        }

        return $encomienda;
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
        | VIAJES
        |--------------------------------------------------------------------------
        */

        $viajes =
            Viaje::query()
                ->with([
                    'vehiculoChoferRuta.ruta',
                    'vehiculoChoferRuta.asignacion.chofer.usuario',
                    'vehiculoChoferRuta.asignacion.vehiculo',
                ])
                ->orderByDesc(
                    'id'
                )
                ->get()
                ->map(
                    function (
                        Viaje $viaje
                    ): array {
                        $vehiculoChoferRuta =
                            $viaje
                                ->vehiculoChoferRuta;

                        $ruta =
                            $vehiculoChoferRuta
                                ?->ruta;

                        $asignacion =
                            $vehiculoChoferRuta
                                ?->asignacion;

                        $chofer =
                            $asignacion
                                ?->chofer;

                        $usuario =
                            $chofer
                                ?->usuario;

                        $vehiculo =
                            $asignacion
                                ?->vehiculo;

                        $nombreChofer =
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
                                $viaje->id,

                            'estado' =>
                                $viaje->estado,

                            'hora_inicio' =>
                                $vehiculoChoferRuta
                                    ?->hora_inicio
                                    ?->format(
                                        'Y-m-d H:i:s'
                                    ),

                            'ruta' =>
                                $ruta
                                    ? [
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
                                    : null,

                            'chofer' =>
                                $chofer
                                    ? [
                                        'id' =>
                                            (int)
                                            $chofer->id,

                                        'nombre' =>
                                            $nombreChofer,

                                        'ci' =>
                                            $usuario?->ci,

                                        'carnet_sindical' =>
                                            $chofer
                                                ->carnet_sindical,
                                    ]
                                    : null,

                            'vehiculo' =>
                                $vehiculo
                                    ? [
                                        'id' =>
                                            (int)
                                            $vehiculo->id,

                                        'placa' =>
                                            $vehiculo->placa,

                                        'tipo' =>
                                            $vehiculo->tipo,

                                        'marca' =>
                                            $vehiculo->marca,

                                        'modelo' =>
                                            $vehiculo->modelo,

                                        'color' =>
                                            $vehiculo->color,

                                        'estado' =>
                                            $vehiculo->estado,
                                    ]
                                    : null,
                        ];
                    }
                )
                ->values();

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
            'rutas' =>
                $rutas,
            'viajes' =>
                $viajes,
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

                            'id_ruta' =>
                                (int)
                                $data[
                                    'id_ruta'
                                ],

                            'remitente' =>
                                $data[
                                    'remitente'
                                ],

                            'destinatario' =>
                                $data[
                                    'destinatario'
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
                
                $encomienda->fill([

                    'id_ruta' =>
                        (int)
                        $data[
                            'id_ruta'
                        ],

                    'remitente' =>
                        $data[
                            'remitente'
                        ],

                    'destinatario' =>
                        $data[
                            'destinatario'
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
                ]);

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
                            'Solo se puede asignar una encomienda en estado Registrada.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | VALIDAR SI YA TIENE VIAJE
                |--------------------------------------------------------------------------
                */

                $yaAsignada =
                    ViajeEncomienda::query()
                        ->where(
                            'id_encomienda',
                            $encomienda->id
                        )
                        ->exists();

                if (
                    $yaAsignada
                ) {
                    throw ValidationException::withMessages([
                        'id_viaje' =>
                            'La encomienda ya se encuentra asignada a un viaje.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | OBTENER VIAJE
                |--------------------------------------------------------------------------
                */

                /** @var Viaje $viaje */
                $viaje =
                    Viaje::query()
                        ->with([
                            'vehiculoChoferRuta.ruta',
                            'vehiculoChoferRuta.asignacion.chofer.usuario',
                            'vehiculoChoferRuta.asignacion.vehiculo',
                        ])
                        ->findOrFail(
                            (int)
                            $data[
                                'id_viaje'
                            ]
                        );

                /*
                |--------------------------------------------------------------------------
                | VALIDAR CONFIGURACIÓN DEL VIAJE
                |--------------------------------------------------------------------------
                */

                $vehiculoChoferRuta =
                    $viaje
                        ->vehiculoChoferRuta;

                if (
                    !$vehiculoChoferRuta
                ) {
                    throw ValidationException::withMessages([
                        'id_viaje' =>
                            'El viaje seleccionado no tiene una asignación de vehículo, chofer y ruta.',
                    ]);
                }

                $ruta =
                    $vehiculoChoferRuta
                        ->ruta;

                if (
                    !$ruta
                ) {
                    throw ValidationException::withMessages([
                        'id_viaje' =>
                            'El viaje seleccionado no tiene una ruta asignada.',
                    ]);
                }

                if (
                    !$vehiculoChoferRuta
                        ->asignacion
                ) {
                    throw ValidationException::withMessages([
                        'id_viaje' =>
                            'El viaje seleccionado no tiene una asignación de vehículo y chofer.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | VALIDAR ORIGEN Y DESTINO
                |--------------------------------------------------------------------------
                */

                if (
                    (int)
                    $encomienda->id_ruta !==
                    (int)
                    $vehiculoChoferRuta->id_ruta
                ) {
                    throw ValidationException::withMessages([
                        'id_viaje' =>
                            'El viaje seleccionado no corresponde a la ruta de la encomienda.',
                    ]);
                }

                $before =
                    $this->snapshot(
                        $encomienda
                    );

                /*
                |--------------------------------------------------------------------------
                | ASIGNAR ENCOMIENDA AL VIAJE
                |--------------------------------------------------------------------------
                */

                ViajeEncomienda::query()
                    ->create([
                        'id_viaje' =>
                            (int)
                            $viaje->id,

                        'id_encomienda' =>
                            (int)
                            $encomienda->id,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | CAMBIAR ESTADO
                |--------------------------------------------------------------------------
                */

                $encomienda->estado =
                    'En tránsito';

                $encomienda->qr_token =
                    $encomienda->qr_token
                        ?: Str::random(
                            64
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
                    ViajeEncomienda::query()
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
                    ViajeEncomienda::query()
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
                'viajeEncomienda.viaje',
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

            'id_viaje' =>
                $encomienda
                    ->viajeEncomienda
                    ?->id_viaje,

            'id_vehiculo_chofer_ruta' =>
                $encomienda
                    ->viajeEncomienda
                    ?->viaje
                    ?->id_vehiculo_chofer_ruta,
        ];
    }
}