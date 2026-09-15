<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Services;

use App\Shared\Models\AsignacionVehiculoChofer;
use App\Shared\Models\Propietario;
use App\Shared\Models\Vehiculo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VehiculoReporteService
{
    /**
     * Reporte: Lista completa de vehículos.
     *
     * @return array{reporte: array<string, mixed>, filtros: array<string, mixed>}
     */
    public function listaVehiculos(array $filtros): array
    {
        $query = Vehiculo::query()
            ->with(['categoria', 'propietario.chofer.usuario']);

        $this->aplicarFiltrosFecha($query, $filtros, 'vehiculo.created_at');

        if (!empty($filtros['estado'])) {
            $query->where('vehiculo.estado', $filtros['estado']);
        }

        if (!empty($filtros['id_categoria'])) {
            $query->where('vehiculo.id_categoria', (int) $filtros['id_categoria']);
        }

        $vehiculos = $query->orderByDesc('vehiculo.created_at')->get();

        return [
            'tipo' => 'lista_vehiculos',
            'titulo' => 'Lista de vehículos',
            'total_registros' => $vehiculos->count(),
            'items' => $vehiculos,
            'resumen_estado' => $this->contarPorEstado($vehiculos),
        ];
    }

    /**
     * Reporte: Vehículos disponibles (Operativos sin asignación activa).
     *
     * @return array{reporte: array<string, mixed>, filtros: array<string, mixed>}
     */
    public function vehiculosDisponibles(array $filtros): array
    {
        $query = Vehiculo::query()
            ->with(['categoria', 'propietario.chofer.usuario'])
            ->where('vehiculo.estado', 'Operativo')
            ->whereDoesntHave('asignaciones', function (Builder $q): void {
                $q->where('estado', 'Activo')
                    ->whereNull('deleted_at');
            });

        $this->aplicarFiltrosFecha($query, $filtros, 'vehiculo.created_at');

        if (!empty($filtros['id_categoria'])) {
            $query->where('vehiculo.id_categoria', (int) $filtros['id_categoria']);
        }

        $vehiculos = $query->orderByDesc('vehiculo.created_at')->get();

        return [
            'tipo' => 'vehiculos_disponibles',
            'titulo' => 'Vehículos disponibles',
            'total_registros' => $vehiculos->count(),
            'items' => $vehiculos,
            'capacidad_total' => (int) $vehiculos->sum('capacidad'),
        ];
    }

    /**
     * Reporte: Vehículos asignados (asignaciones activas).
     *
     * @return array{reporte: array<string, mixed>, filtros: array<string, mixed>}
     */
    public function vehiculosAsignados(array $filtros): array
    {
        $query = AsignacionVehiculoChofer::query()
            ->with(['vehiculo.categoria', 'chofer.usuario'])
            ->where('estado', 'Activo')
            ->whereNull('deleted_at');

        $this->aplicarFiltrosFecha($query, $filtros, 'asignacion_vehiculo_chofer.created_at');

        if (!empty($filtros['id_chofer'])) {
            $query->where('id_chofer', (int) $filtros['id_chofer']);
        }

        $asignaciones = $query->orderByDesc('created_at')->get();

        return [
            'tipo' => 'vehiculos_asignados',
            'titulo' => 'Vehículos asignados',
            'total_registros' => $asignaciones->count(),
            'items' => $asignaciones,
            'choferes_unicos' => $asignaciones->pluck('id_chofer')->unique()->count(),
        ];
    }

    /**
     * Reporte: Vehículos por propietario (agrupados por chofer).
     *
     * @return array{reporte: array<string, mixed>, filtros: array<string, mixed>}
     */
    public function vehiculosPorPropietario(array $filtros): array
    {
        $query = Propietario::query()
            ->with(['chofer.usuario', 'vehiculo.categoria'])
            ->whereNull('deleted_at');

        $this->aplicarFiltrosFecha($query, $filtros, 'propietario.created_at');

        if (!empty($filtros['id_chofer'])) {
            $query->where('id_chofer', (int) $filtros['id_chofer']);
        }

        $propietarios = $query->orderByDesc('created_at')->get();

        // Agrupar por chofer (propietario).
        $grupos = $propietarios->groupBy('id_chofer')->map(function (Collection $items): array {
            /** @var Propietario $first */
            $first = $items->first();
            $usuario = $first->chofer?->usuario;

            return [
                'id_chofer' => $first->id_chofer,
                'nombre_completo' => trim(implode(' ', array_filter([
                    $usuario?->nombres,
                    $usuario?->primer_apellido !== '-' ? $usuario?->primer_apellido : '',
                    $usuario?->segundo_apellido,
                ]))),
                'ci' => $usuario?->ci,
                'carnet_sindical' => $first->chofer?->carnet_sindical,
                'total_vehiculos' => $items->count(),
                'vehiculos' => $items->pluck('vehiculo')->filter()->values(),
            ];
        })->values();

        return [
            'tipo' => 'vehiculos_por_propietario',
            'titulo' => 'Vehículos por propietario',
            'total_registros' => $propietarios->count(),
            'total_propietarios' => $grupos->count(),
            'items' => $grupos,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function aplicarFiltrosFecha(Builder $query, array $filtros, string $columna): void
    {
        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate($columna, '>=', $filtros['fecha_inicio']);
        }
        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate($columna, '<=', $filtros['fecha_fin']);
        }
    }

    /**
     * @param  Collection<int, Vehiculo>  $vehiculos
     * @return array<string, int>
     */
    private function contarPorEstado(Collection $vehiculos): array
    {
        return [
            'Operativo' => $vehiculos->where('estado', 'Operativo')->count(),
            'En mantenimiento' => $vehiculos->where('estado', 'En mantenimiento')->count(),
            'Baja' => $vehiculos->where('estado', 'Baja')->count(),
        ];
    }
    public function obtener(string $tipo, array $filtros): array
    {
        return match ($tipo) {
            'lista' => $this->listaVehiculos($filtros),
            'disponibles' => $this->vehiculosDisponibles($filtros),
            'asignados' => $this->vehiculosAsignados($filtros),
            'por_propietario' => $this->vehiculosPorPropietario($filtros),
            default => throw new \InvalidArgumentException(
                'El tipo de reporte de vehículos no es válido.'
            ),
        };
    }
}