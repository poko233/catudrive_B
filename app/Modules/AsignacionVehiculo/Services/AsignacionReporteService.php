<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Services;

use App\Shared\Models\AsignacionVehiculoChofer;
use App\Shared\Models\Vehiculo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AsignacionReporteService
{
    /*
    |--------------------------------------------------------------------------
    | VEHÍCULOS ASIGNADOS POR CHOFER
    |--------------------------------------------------------------------------
    |
    | Solo toma asignaciones activas.
    | Se agrupan por chofer para mantener el sentido del reporte solicitado.
    |
    */

    public function vehiculosAsignadosPorChofer(
        array $filtros
    ): array {
        $query =
            AsignacionVehiculoChofer::query()
                ->with([
                    'chofer.usuario',
                    'vehiculo.categoria',
                ])
                ->where(
                    'estado',
                    'Activo'
                );

        $this->aplicarFiltrosFechaAsignacion(
            $query,
            $filtros
        );

        $asignaciones =
            $query
                ->orderByDesc(
                    'fecha_asignacion'
                )
                ->orderByDesc(
                    'id'
                )
                ->get();

        $grupos =
            $asignaciones
                ->groupBy(
                    'id_chofer'
                )
                ->map(
                    function (
                        Collection $items
                    ): array {
                        /** @var AsignacionVehiculoChofer $primera */
                        $primera =
                            $items->first();

                        $usuario =
                            $primera
                                ->chofer
                                ?->usuario;

                        $nombre =
                            trim(
                                implode(
                                    ' ',
                                    array_filter([
                                        $usuario?->nombres,

                                        $usuario?->primer_apellido !== '-'
                                            ? $usuario?->primer_apellido
                                            : '',

                                        $usuario?->segundo_apellido,
                                    ])
                                )
                            );

                        return [
                            'id_chofer' =>
                                (int)
                                $primera->id_chofer,

                            'nombre_chofer' =>
                                $nombre,

                            'ci' =>
                                $usuario?->ci,

                            'carnet_sindical' =>
                                $primera
                                    ->chofer
                                    ?->carnet_sindical,

                            'total_vehiculos' =>
                                $items->count(),

                            'asignaciones' =>
                                $items->values(),
                        ];
                    }
                )
                ->sortBy(
                    static fn (
                        array $grupo
                    ) =>
                        mb_strtolower(
                            (string)
                            $grupo[
                                'nombre_chofer'
                            ]
                        )
                )
                ->values();

        return [
            'tipo' =>
                'vehiculos_asignados_por_chofer',

            'titulo' =>
                'Vehículos asignados por chofer',

            'total_asignaciones' =>
                $asignaciones->count(),

            'total_choferes' =>
                $grupos->count(),

            'items' =>
                $grupos,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORIAL DE ASIGNACIONES
    |--------------------------------------------------------------------------
    */

    public function historialAsignaciones(
        array $filtros
    ): array {
        $query =
            AsignacionVehiculoChofer::query()
                ->with([
                    'chofer.usuario',
                    'vehiculo.categoria',
                ]);

        $this->aplicarFiltrosFechaAsignacion(
            $query,
            $filtros
        );

        if (
            !empty(
                $filtros[
                    'estado_asignacion'
                ]
            )
        ) {
            $query->where(
                'estado',
                $filtros[
                    'estado_asignacion'
                ]
            );
        }

        $asignaciones =
            $query
                ->orderByDesc(
                    'fecha_asignacion'
                )
                ->orderByDesc(
                    'id'
                )
                ->get();

        return [
            'tipo' =>
                'historial_asignaciones',

            'titulo' =>
                'Historial de asignaciones',

            'total_registros' =>
                $asignaciones->count(),

            'items' =>
                $asignaciones,

            'resumen_estado' => [
                'Activo' =>
                    $asignaciones
                        ->where(
                            'estado',
                            'Activo'
                        )
                        ->count(),

                'Inactivo' =>
                    $asignaciones
                        ->where(
                            'estado',
                            'Inactivo'
                        )
                        ->count(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VEHÍCULOS SIN ASIGNAR
    |--------------------------------------------------------------------------
    |
    | "Sin asignar" significa que el vehículo no tiene una asignación ACTIVA.
    | A diferencia de "vehículos disponibles", aquí no forzamos estado Operativo:
    | el reporte puede mostrar también mantenimiento o baja.
    |
    */

    public function vehiculosSinAsignar(
        array $filtros
    ): array {
        $query =
            Vehiculo::query()
                ->with(
                    'categoria'
                )
                ->whereDoesntHave(
                    'asignaciones',
                    function (
                        Builder $asignacionQuery
                    ): void {
                        $asignacionQuery
                            ->where(
                                'estado',
                                'Activo'
                            );
                    }
                );

        if (
            !empty(
                $filtros[
                    'estado_vehiculo'
                ]
            )
        ) {
            $query->where(
                'estado',
                $filtros[
                    'estado_vehiculo'
                ]
            );
        }

        $vehiculos =
            $query
                ->orderBy(
                    'placa'
                )
                ->get();

        return [
            'tipo' =>
                'vehiculos_sin_asignar',

            'titulo' =>
                'Vehículos sin asignar',

            'total_registros' =>
                $vehiculos->count(),

            'items' =>
                $vehiculos,

            'resumen_estado' =>
                $this->contarVehiculosPorEstado(
                    $vehiculos
                ),

            'capacidad_total' =>
                (int)
                $vehiculos->sum(
                    'capacidad'
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    private function aplicarFiltrosFechaAsignacion(
        Builder $query,
        array $filtros
    ): void {
        if (
            !empty(
                $filtros[
                    'fecha_inicio'
                ]
            )
        ) {
            $query->whereDate(
                'fecha_asignacion',
                '>=',
                $filtros[
                    'fecha_inicio'
                ]
            );
        }

        if (
            !empty(
                $filtros[
                    'fecha_fin'
                ]
            )
        ) {
            $query->whereDate(
                'fecha_asignacion',
                '<=',
                $filtros[
                    'fecha_fin'
                ]
            );
        }
    }

    /**
     * @param Collection<int, Vehiculo> $vehiculos
     * @return array<string, int>
     */
    private function contarVehiculosPorEstado(
        Collection $vehiculos
    ): array {
        return [
            'Operativo' =>
                $vehiculos
                    ->where(
                        'estado',
                        'Operativo'
                    )
                    ->count(),

            'En mantenimiento' =>
                $vehiculos
                    ->where(
                        'estado',
                        'En mantenimiento'
                    )
                    ->count(),

            'Baja' =>
                $vehiculos
                    ->where(
                        'estado',
                        'Baja'
                    )
                    ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVER REPORTE
    |--------------------------------------------------------------------------
    */

    public function obtener(
        string $tipo,
        array $filtros
    ): array {
        return match (
            $tipo
        ) {
            'por_chofer' =>
                $this->vehiculosAsignadosPorChofer(
                    $filtros
                ),

            'historial' =>
                $this->historialAsignaciones(
                    $filtros
                ),

            'sin_asignar' =>
                $this->vehiculosSinAsignar(
                    $filtros
                ),

            default =>
                throw new \InvalidArgumentException(
                    'El tipo de reporte de asignaciones no es válido.'
                ),
        };
    }
}
