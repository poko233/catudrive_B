<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Services;

use App\Modules\Pasaje\Services\ChoferContextService;
use App\Shared\Models\Encomienda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EncomiendaReporteService
{
    public function __construct(
        private readonly ChoferContextService $choferContext,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BASE
    |--------------------------------------------------------------------------
    */

    private function queryBase(): Builder
    {
        $query = Encomienda::query()
            ->with([
                'ruta',
                'viajeEncomienda.viaje.vehiculoChoferRuta.ruta',
                'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion.chofer.usuario',
                'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion.vehiculo',
            ]);

        $idChofer = $this->choferContext->idChoferActual();

        if ($idChofer !== null) {
            $query->whereHas(
                'viajeEncomienda.viaje.vehiculoChoferRuta.asignacion',
                fn (Builder $asignacion) =>
                    $asignacion->where('id_chofer', $idChofer)
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | APLICAR FILTROS
    |--------------------------------------------------------------------------
    */

    private function aplicarFiltros(
        Builder $query,
        array $filtros
    ): Builder {
        if (
            !empty(
                $filtros[
                    'fecha_inicio'
                ]
            )
        ) {
            $query->whereDate(
                'created_at',
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
                'created_at',
                '<=',
                $filtros[
                    'fecha_fin'
                ]
            );
        }

        if (
            !empty(
                $filtros[
                    'id_ruta'
                ]
            )
        ) {
            $query->where(
                'id_ruta',
                (int)
                $filtros[
                    'id_ruta'
                ]
            );

            $query->where(
                'estado',
                '!=',
                'Anulada'
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | OBTENER REPORTE POR TIPO
    |--------------------------------------------------------------------------
    */

    public function obtener(
        string $tipo,
        array $filtros
    ): array {
        return match ($tipo) {
            'registradas' =>
                $this->registradas(
                    $filtros
                ),

            'pendientes' =>
                $this->pendientes(
                    $filtros
                ),

            'entregadas' =>
                $this->entregadas(
                    $filtros
                ),

            'por_destino' =>
                $this->porDestino(
                    $filtros
                ),

            'ingresos' =>
                $this->ingresos(
                    $filtros
                ),

            default =>
                throw new \InvalidArgumentException(
                    'El tipo de reporte de encomiendas no es válido.'
                ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | ENCOMIENDAS REGISTRADAS
    |--------------------------------------------------------------------------
    */

    public function registradas(
        array $filtros
    ): array {
        $items =
            $this
                ->aplicarFiltros(
                    $this
                        ->queryBase()
                        ->where(
                            'estado',
                            'Registrada'
                        ),
                    $filtros
                )
                ->orderByDesc(
                    'created_at'
                )
                ->get();

        return [
            'tipo' =>
                'registradas',

            'titulo' =>
                'Encomiendas Registradas',

            'total_registros' =>
                $items->count(),

            'items' =>
                $items,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ENCOMIENDAS PENDIENTES
    |--------------------------------------------------------------------------
    */

    public function pendientes(
        array $filtros
    ): array {
        $items =
            $this
                ->aplicarFiltros(
                    $this
                        ->queryBase()
                        ->whereIn(
                            'estado',
                            [
                                'Registrada',
                                'En tránsito',
                            ]
                        ),
                    $filtros
                )
                ->orderByDesc(
                    'created_at'
                )
                ->get();

        return [
            'tipo' =>
                'pendientes',

            'titulo' =>
                'Encomiendas Pendientes',

            'total_registros' =>
                $items->count(),

            'items' =>
                $items,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ENCOMIENDAS ENTREGADAS
    |--------------------------------------------------------------------------
    */

    public function entregadas(
        array $filtros
    ): array {
        $items =
            $this
                ->aplicarFiltros(
                    $this
                        ->queryBase()
                        ->where(
                            'estado',
                            'Entregada'
                        ),
                    $filtros
                )
                ->orderByDesc(
                    'created_at'
                )
                ->get();

        return [
            'tipo' =>
                'entregadas',

            'titulo' =>
                'Encomiendas Entregadas',

            'total_registros' =>
                $items->count(),

            'items' =>
                $items,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ENCOMIENDAS POR DESTINO
    |--------------------------------------------------------------------------
    */

    public function porDestino(
        array $filtros
    ): array {
        $items =
            $this
                ->aplicarFiltros(
                    $this->queryBase(),
                    $filtros
                )
                ->orderByDesc(
                    'created_at'
                )
                ->get()
                ->sortBy(
                    fn (
                        Encomienda $encomienda
                    ): string =>
                        mb_strtoupper(
                            (string)
                            (
                                $encomienda
                                    ->ruta
                                    ?->destino
                                ?? ''
                            )
                        )
                )
                ->values();

        $resumenDestinos =
            $items
                ->groupBy(
                    function (
                        Encomienda $encomienda
                    ): string {
                        return
                            $encomienda
                                ->ruta
                                ?->destino
                            ?? 'Sin destino';
                    }
                )
                ->map(
                    function (
                        Collection $grupo,
                        string $destino
                    ): array {
                        return [
                            'destino' =>
                                $destino,

                            'cantidad' =>
                                $grupo->count(),

                            'total' =>
                                number_format(
                                    (float)
                                    $grupo->sum(
                                        'precio'
                                    ),
                                    2,
                                    '.',
                                    ''
                                ),
                        ];
                    }
                )
                ->values();

        return [
            'tipo' =>
                'por_destino',

            'titulo' =>
                'Encomiendas por Destino',

            'total_registros' =>
                $items->count(),

            'total_destinos' =>
                $resumenDestinos
                    ->count(),

            'resumen_destinos' =>
                $resumenDestinos,

            'items' =>
                $items,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | INGRESOS
    |--------------------------------------------------------------------------
    */

    public function ingresos(
        array $filtros
    ): array {
        $items =
            $this
                ->aplicarFiltros(
                    $this
                        ->queryBase()
                        ->where(
                            'estado',
                            '!=',
                            'Anulada'
                        ),
                    $filtros
                )
                ->orderByDesc(
                    'created_at'
                )
                ->get();

        $totalIngresos =
            $items
                ->sum(
                    fn (
                        Encomienda $item
                    ) =>
                        (float)
                        $item->precio
                );

        return [
            'tipo' =>
                'ingresos',

            'titulo' =>
                'Ingresos por Encomiendas',

            'total_registros' =>
                $items->count(),

            'total_ingresos' =>
                number_format(
                    $totalIngresos,
                    2,
                    '.',
                    ''
                ),

            'items' =>
                $items,
        ];
    }
}
