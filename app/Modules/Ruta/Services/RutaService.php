<?php

declare(strict_types=1);

namespace App\Modules\Ruta\Services;

use App\Shared\Models\Ruta;
use App\Shared\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RutaService
{
    public function __construct(
        private readonly AuditService $audit
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BASE
    |--------------------------------------------------------------------------
    |
    | Calculamos también cuántos viajes utilizaron la ruta.
    |
    | Esto evita hacer una consulta adicional por cada fila.
    |
    */

    private function queryBase(): Builder
    {
        return Ruta::query()
            ->select(
                'ruta.*'
            )
            ->selectSub(
                function (
                    $query
                ) {
                    $query
                        ->from(
                            'vehiculo_chofer_ruta'
                        )
                        ->selectRaw(
                            'COUNT(*)'
                        )
                        ->whereColumn(
                            'vehiculo_chofer_ruta.id_ruta',
                            'ruta.id'
                        );
                },

                'viajes_count'
            );
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
            ->orderBy(
                'estado'
            )
            ->orderBy(
                'origen'
            )
            ->orderBy(
                'destino'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | DETALLE
    |--------------------------------------------------------------------------
    */

    public function obtener(
        int $id
    ): Ruta {
        return $this
            ->queryBase()
            ->findOrFail(
                $id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $data
    ): Ruta {
        $ruta = null;

        DB::transaction(
            function () use (
                $data,
                &$ruta
            ): void {
                $ruta =
                    Ruta::query()
                        ->create([
                            'origen' =>
                                (string)
                                $data[
                                    'origen'
                                ],

                            'destino' =>
                                (string)
                                $data[
                                    'destino'
                                ],

                            'hora_inicio' =>
                                $data[
                                    'hora_inicio'
                                ] ?? null,

                            'hora_fin' =>
                                $data[
                                    'hora_fin'
                                ] ?? null,

                            'tarifa' =>
                                (float)
                                $data[
                                    'tarifa'
                                ],

                            'estado' =>
                                $this->estadoParaBase(
                                    (string)
                                    $data[
                                        'estado'
                                    ]
                                ),
                        ]);
            }
        );

        if (
            !$ruta instanceof
            Ruta
        ) {
            throw new RuntimeException(
                'No se pudo registrar la ruta.'
            );
        }

        $fresh =
            $this->obtener(
                (int)
                $ruta->id
            );

        $this->audit
            ->created(
                resource:
                    'Ruta',

                resourceId:
                    (int)
                    $fresh->id,

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
    ): Ruta {
        $before = [];

        DB::transaction(
            function () use (
                $id,
                $data,
                &$before
            ): void {
                /** @var Ruta $ruta */
                $ruta =
                    Ruta::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                $before =
                    $this->snapshot(
                        $ruta
                    );

                $ruta->origen =
                    (string)
                    $data[
                        'origen'
                    ];

                $ruta->destino =
                    (string)
                    $data[
                        'destino'
                    ];

                $ruta->hora_inicio =
                    $data[
                        'hora_inicio'
                    ] ?? null;

                $ruta->hora_fin =
                    $data[
                        'hora_fin'
                    ] ?? null;

                $ruta->tarifa =
                    (float)
                    $data[
                        'tarifa'
                    ];

                $ruta->estado =
                    $this->estadoParaBase(
                        (string)
                        $data[
                            'estado'
                        ]
                    );

                $ruta->save();
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        $this->audit
            ->updated(
                resource:
                    'Ruta',

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
    | BAJA LÓGICA
    |--------------------------------------------------------------------------
    |
    | NO eliminamos físicamente la ruta.
    |
    | vehiculo_chofer_ruta tiene una FK restrict hacia ruta.
    |
    | Además necesitamos conservar los viajes históricos.
    |
    */

    public function darDeBaja(
        int $id
    ): Ruta {
        $before = [];

        DB::transaction(
            function () use (
                $id,
                &$before
            ): void {
                /** @var Ruta $ruta */
                $ruta =
                    Ruta::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                $before =
                    $this->snapshot(
                        $ruta
                    );

                $ruta->estado =
                    'Inactiva';

                $ruta->save();
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        $this->audit
            ->updated(
                resource:
                    'Ruta',

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
    | SNAPSHOT AUDITORÍA
    |--------------------------------------------------------------------------
    */

    private function snapshot(
        Ruta $ruta
    ): array {
        return [
            'id' =>
                (int)
                $ruta->id,

            'origen' =>
                $ruta->origen,

            'destino' =>
                $ruta->destino,

            'hora_inicio' =>
                $ruta->hora_inicio
                    ?->format(
                        'Y-m-d H:i'
                    ),

            'hora_fin' =>
                $ruta->hora_fin
                    ?->format(
                        'Y-m-d H:i'
                    ),

            'tarifa' =>
                (float)
                $ruta->tarifa,

            'estado' =>
                $ruta->estado,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADO BD
    |--------------------------------------------------------------------------
    */

    private function estadoParaBase(
        string $estado
    ): string {
        return
            mb_strtoupper(
                trim(
                    $estado
                )
            ) ===
            'INACTIVA'
                ? 'Inactiva'
                : 'Activa';
    }
}