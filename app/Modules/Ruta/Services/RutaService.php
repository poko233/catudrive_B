<?php

declare(strict_types=1);

namespace App\Modules\Ruta\Services;

use App\Shared\Models\Ruta;
use App\Shared\Services\AuditService;
use App\Shared\Traits\GeneraUrlArchivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RutaService
{
    use GeneraUrlArchivo;

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
    | CHOFERES CON VIAJES REALES EN UNA RUTA
    |--------------------------------------------------------------------------
    |
    | Importante:
    |
    | No basta con mirar vehiculo_chofer_ruta.
    |
    | Hacemos JOIN con `viaje`, por lo tanto el chofer solamente aparece
    | cuando existe realmente al menos un registro de viaje para esa ruta.
    |
    | Si el mismo chofer tiene 5 viajes en esa ruta aparece una sola vez,
    | con viajes_count = 5.
    |
    */

    public function choferesConViajes(
        int $idRuta
    ): array {
        $ruta =
            Ruta::query()
                ->findOrFail(
                    $idRuta
                );

        $items =
            DB::table(
                'viaje as v'
            )
                ->join(
                    'vehiculo_chofer_ruta as vcr',
                    'vcr.id',
                    '=',
                    'v.id_vehiculo_chofer_ruta'
                )
                ->join(
                    'asignacion_vehiculo_chofer as avc',
                    'avc.id',
                    '=',
                    'vcr.id_asignacion_vehiculo_chofer'
                )
                ->join(
                    'chofer as c',
                    'c.id',
                    '=',
                    'avc.id_chofer'
                )
                ->join(
                    'user as u',
                    'u.id',
                    '=',
                    'c.id'
                )
                ->where(
                    'vcr.id_ruta',
                    $idRuta
                )
                ->select([
                    'u.id',
                    'u.nombres',
                    'u.primer_apellido',
                    'u.segundo_apellido',
                    'u.ci',
                    'u.celular',
                    'u.foto',
                    'u.estado',
                    'c.carnet_sindical',
                ])
                ->selectRaw(
                    'COUNT(DISTINCT v.id) AS viajes_count'
                )
                ->selectRaw(
                    'MIN(vcr.hora_inicio) AS primera_salida'
                )
                ->selectRaw(
                    'MAX(vcr.hora_inicio) AS ultima_salida'
                )
                ->groupBy([
                    'u.id',
                    'u.nombres',
                    'u.primer_apellido',
                    'u.segundo_apellido',
                    'u.ci',
                    'u.celular',
                    'u.foto',
                    'u.estado',
                    'c.carnet_sindical',
                ])
                ->orderBy(
                    'u.nombres'
                )
                ->orderBy(
                    'u.primer_apellido'
                )
                ->get();

        $choferes =
            $items
                ->map(
                    function (
                        object $item
                    ): array {
                        $nombreCompleto =
                            trim(
                                preg_replace(
                                    '/\s+/u',
                                    ' ',
                                    implode(
                                        ' ',
                                        array_filter([
                                            $item->nombres,
                                            $item->primer_apellido !== '-'
                                                ? $item->primer_apellido
                                                : '',
                                            $item->segundo_apellido,
                                        ])
                                    )
                                )
                                ?? ''
                            );

                        return [
                            'id' =>
                                (int)
                                $item->id,

                            'nombre_completo' =>
                                $nombreCompleto,

                            'carnet_identidad' =>
                                $item->ci,

                            'carnet_sindical' =>
                                $item->carnet_sindical,

                            'telefono' =>
                                $item->celular,

                            'fotografia' =>
                                $item->foto,

                            'fotoUrl' =>
                                $this->urlArchivo(
                                    $item->foto
                                ),

                            'estado' =>
                                $item->estado
                                    ? mb_strtoupper(
                                        trim(
                                            (string)
                                            $item->estado
                                        )
                                    )
                                    : null,

                            'viajes_count' =>
                                (int)
                                $item->viajes_count,

                            'primera_salida' =>
                                $this->dateTime(
                                    $item->primera_salida
                                ),

                            'ultima_salida' =>
                                $this->dateTime(
                                    $item->ultima_salida
                                ),
                        ];
                    }
                )
                ->values();

        return [
            'ruta' => [
                'id' =>
                    (int)
                    $ruta->id,

                'origen' =>
                    $ruta->origen,

                'destino' =>
                    $ruta->destino,
            ],

            'total' =>
                $choferes->count(),

            'choferes' =>
                $choferes,
        ];
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

    private function dateTime(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        return Carbon::parse(
            (string)
            $value
        )->format(
            'Y-m-d H:i:s'
        );
    }
}
