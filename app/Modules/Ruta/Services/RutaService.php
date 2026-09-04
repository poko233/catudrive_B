<?php

declare(strict_types=1);

namespace App\Modules\Ruta\Services;

use App\Shared\Models\Ruta;
use App\Shared\Services\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RutaService
{
    public function __construct(
        private readonly AuditService $audit
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function listar(): Collection
    {
        return Ruta::query()
            ->withCount(
                'viajes'
            )
            ->orderByRaw(
                "
                CASE
                    WHEN estado = 'Activa'
                    THEN 0
                    ELSE 1
                END
                "
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
    | OBTENER
    |--------------------------------------------------------------------------
    */

    public function obtener(
        int $id
    ): Ruta {
        return Ruta::query()
            ->withCount(
                'viajes'
            )
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
        $id =
            0;

        DB::transaction(
            function () use (
                $data,
                &$id
            ): void {
                $ruta =
                    Ruta::query()
                        ->create(
                            $this->payload(
                                $data
                            )
                        );

                $id =
                    (int)
                    $ruta->id;
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        $this->audit
            ->created(
                resource:
                    'Ruta',

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
    ): Ruta {
        $before =
            [];

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

                $ruta->fill(
                    $this->payload(
                        $data
                    )
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
    | BAJA
    |--------------------------------------------------------------------------
    |
    | Baja lógica.
    |
    | No eliminamos la ruta porque puede existir
    | historial relacionado a ella.
    |
    */

    public function darBaja(
        int $id
    ): Ruta {
        $before =
            [];

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

                if (
                    mb_strtoupper(
                        trim(
                            (string)
                            $ruta->estado
                        )
                    ) === 'INACTIVA'
                ) {
                    return;
                }

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
    | PAYLOAD
    |--------------------------------------------------------------------------
    */

    private function payload(
        array $data
    ): array {
        return [
            'origen' =>
                trim(
                    (string)
                    $data[
                        'origen'
                    ]
                ),

            'destino' =>
                trim(
                    (string)
                    $data[
                        'destino'
                    ]
                ),

            'fecha_inicio' =>
                $data[
                    'fecha_inicio'
                ] ?? null,

            'hora_inicio' =>
                $data[
                    'hora_inicio'
                ] ?? null,

            'fecha_fin' =>
                $data[
                    'fecha_fin'
                ] ?? null,

            'hora_fin' =>
                $data[
                    'hora_fin'
                ] ?? null,

            'tarifa' =>
                $data[
                    'tarifa'
                ],

            'estado' =>
                $this->dbEstado(
                    (string)
                    $data[
                        'estado'
                    ]
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADO DB
    |--------------------------------------------------------------------------
    */

    private function dbEstado(
        string $estado
    ): string {
        return
            mb_strtoupper(
                trim(
                    $estado
                )
            ) === 'INACTIVA'
                ? 'Inactiva'
                : 'Activa';
    }

    /*
    |--------------------------------------------------------------------------
    | SNAPSHOT
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

            'fecha_inicio' =>
                $ruta->fecha_inicio
                    ?->format(
                        'Y-m-d'
                    ),

            'hora_inicio' =>
                $this->time(
                    $ruta->hora_inicio
                ),

            'fecha_fin' =>
                $ruta->fecha_fin
                    ?->format(
                        'Y-m-d'
                    ),

            'hora_fin' =>
                $this->time(
                    $ruta->hora_fin
                ),

            'tarifa' =>
                (string)
                $ruta->tarifa,

            'estado' =>
                $ruta->estado,
        ];
    }

    private function time(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        $value =
            trim(
                (string)
                $value
            );

        return
            mb_strlen(
                $value
            ) >= 5
                ? mb_substr(
                    $value,
                    0,
                    5
                )
                : $value;
    }
}