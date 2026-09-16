<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Services;

use App\Shared\Models\Chofer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ChoferReporteService
{
    /*
    |--------------------------------------------------------------------------
    | LISTA GENERAL DE CHOFERES
    |--------------------------------------------------------------------------
    */

    public function listaChoferes(
        array $filtros
    ): array {
        $query = Chofer::query()
            ->with(
                'usuario'
            );

        $this->aplicarFiltrosFecha(
            $query,
            $filtros,
            'chofer.created_at'
        );

        $this->aplicarFiltroEstado(
            $query,
            $filtros
        );

        $choferes = $query
            ->orderByDesc(
                'chofer.created_at'
            )
            ->get();

        return [
            'tipo' =>
                'lista_choferes',

            'titulo' =>
                'Lista general de choferes',

            'total_registros' =>
                $choferes->count(),

            'items' =>
                $choferes,

            'resumen_estado' =>
                $this->contarPorEstado(
                    $choferes
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CHOFERES ACTIVOS E INACTIVOS
    |--------------------------------------------------------------------------
    |
    | Este documento muestra ambos grupos en un mismo reporte.
    | Solo aplica el filtro de fechas.
    |
    */

    public function choferesActivosInactivos(
        array $filtros
    ): array {
        $query = Chofer::query()
            ->with(
                'usuario'
            );

        $this->aplicarFiltrosFecha(
            $query,
            $filtros,
            'chofer.created_at'
        );

        $choferes = $query
            ->orderByDesc(
                'chofer.created_at'
            )
            ->get();

        $activos = $choferes
            ->filter(
                static fn (
                    Chofer $chofer
                ): bool =>
                    $chofer->usuario?->estado ===
                    'Activo'
            )
            ->values();

        $inactivos = $choferes
            ->filter(
                static fn (
                    Chofer $chofer
                ): bool =>
                    $chofer->usuario?->estado ===
                    'Inactivo'
            )
            ->values();

        return [
            'tipo' =>
                'choferes_activos_inactivos',

            'titulo' =>
                'Choferes activos e inactivos',

            'total_registros' =>
                $choferes->count(),

            'total_activos' =>
                $activos->count(),

            'total_inactivos' =>
                $inactivos->count(),

            'items' =>
                $choferes,

            'activos' =>
                $activos,

            'inactivos' =>
                $inactivos,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CARNETS SINDICALES
    |--------------------------------------------------------------------------
    */

    public function carnetsSindicales(
        array $filtros
    ): array {
        $query = Chofer::query()
            ->with(
                'usuario'
            );

        $this->aplicarFiltrosFecha(
            $query,
            $filtros,
            'chofer.created_at'
        );

        $this->aplicarFiltroEstado(
            $query,
            $filtros
        );

        $choferes = $query
            ->orderBy(
                'carnet_sindical'
            )
            ->get();

        return [
            'tipo' =>
                'carnets_sindicales',

            'titulo' =>
                'Carnets sindicales',

            'total_registros' =>
                $choferes->count(),

            'items' =>
                $choferes,

            'resumen_estado' =>
                $this->contarPorEstado(
                    $choferes
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    private function aplicarFiltrosFecha(
        Builder $query,
        array $filtros,
        string $columna
    ): void {
        if (
            !empty(
                $filtros['fecha_inicio']
            )
        ) {
            $query->whereDate(
                $columna,
                '>=',
                $filtros['fecha_inicio']
            );
        }

        if (
            !empty(
                $filtros['fecha_fin']
            )
        ) {
            $query->whereDate(
                $columna,
                '<=',
                $filtros['fecha_fin']
            );
        }
    }

    private function aplicarFiltroEstado(
        Builder $query,
        array $filtros
    ): void {
        if (
            empty(
                $filtros['estado']
            )
        ) {
            return;
        }

        $estado =
            (string)
            $filtros['estado'];

        $query->whereHas(
            'usuario',
            static function (
                Builder $userQuery
            ) use (
                $estado
            ): void {
                $userQuery->where(
                    'estado',
                    $estado
                );
            }
        );
    }

    /**
     * @param Collection<int, Chofer> $choferes
     * @return array<string, int>
     */
    private function contarPorEstado(
        Collection $choferes
    ): array {
        return [
            'Activo' =>
                $choferes
                    ->filter(
                        static fn (
                            Chofer $chofer
                        ): bool =>
                            $chofer->usuario?->estado ===
                            'Activo'
                    )
                    ->count(),

            'Inactivo' =>
                $choferes
                    ->filter(
                        static fn (
                            Chofer $chofer
                        ): bool =>
                            $chofer->usuario?->estado ===
                            'Inactivo'
                    )
                    ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVER TIPO
    |--------------------------------------------------------------------------
    */

    public function obtener(
        string $tipo,
        array $filtros
    ): array {
        return match (
            $tipo
        ) {
            'lista' =>
                $this->listaChoferes(
                    $filtros
                ),

            'activos_inactivos' =>
                $this->choferesActivosInactivos(
                    $filtros
                ),

            'carnets_sindicales' =>
                $this->carnetsSindicales(
                    $filtros
                ),

            default =>
                throw new \InvalidArgumentException(
                    'El tipo de reporte de choferes no es válido.'
                ),
        };
    }
}
