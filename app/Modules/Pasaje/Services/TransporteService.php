<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\AsignacionVehiculoChofer;
use App\Shared\Models\VehiculoChoferRuta;
use App\Shared\Models\Ruta;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransporteService
{
    /**
     * Lista asignaciones con filtros y relaciones cargadas.
     */
    public function listarAsignaciones(array $filtros, int $perPage = 15): LengthAwarePaginator
    {
        $query = AsignacionVehiculoChofer::query()
            ->with([
                'chofer.usuario',
                'vehiculo',
            ])
            ->orderBy('created_at', 'desc');

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['id_chofer'])) {
            $query->where('id_chofer', $filtros['id_chofer']);
        }

        if (!empty($filtros['id_vehiculo'])) {
            $query->where('id_vehiculo', $filtros['id_vehiculo']);
        }

        if (!empty($filtros['buscar'])) {
            $search = $filtros['buscar'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('chofer.usuario', function ($sq) use ($search) {
                    $sq->where('nombres', 'like', "%{$search}%")
                        ->orWhere('primer_apellido', 'like', "%{$search}%")
                        ->orWhere('segundo_apellido', 'like', "%{$search}%")
                        ->orWhere('ci', 'like', "%{$search}%");
                })->orWhereHas('vehiculo', function ($sq) use ($search) {
                    $sq->where('placa', 'like', "%{$search}%")
                        ->orWhere('marca', 'like', "%{$search}%")
                        ->orWhere('modelo', 'like', "%{$search}%");
                });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea una asignación vehículo-chofer.
     */
    public function crearAsignacion(array $data): AsignacionVehiculoChofer
    {
        return AsignacionVehiculoChofer::query()->create($data);
    }

    /**
     * Actualiza una asignación.
     */
    public function actualizarAsignacion(AsignacionVehiculoChofer $asignacion, array $data): AsignacionVehiculoChofer
    {
        $asignacion->update($data);
        return $asignacion->fresh();
    }

    /**
     * Elimina (soft delete) una asignación.
     */
    public function eliminarAsignacion(AsignacionVehiculoChofer $asignacion): void
    {
        $asignacion->delete();
    }

    /**
     * Lista todas las rutas (para selección). No paginado, o paginado opcional.
     */
    public function listarRutas(array $filtros = [], int $perPage = 15)
    {
        $query = Ruta::query();

        if (!empty($filtros['origen'])) {
            $query->where('origen', 'like', "%{$filtros['origen']}%");
        }
        if (!empty($filtros['destino'])) {
            $query->where('destino', 'like', "%{$filtros['destino']}%");
        }
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Lista relaciones vehiculo_chofer_ruta con filtros.
     */
    public function listarVehiculoChoferRuta(array $filtros, int $perPage = 15)
    {
        $query = VehiculoChoferRuta::query()
            ->with([
                'asignacion.chofer.usuario',
                'asignacion.vehiculo',
                'ruta',
            ])
            ->orderBy('created_at', 'desc');

        if (!empty($filtros['id_asignacion'])) {
            $query->where('id_asignacion_vehiculo_chofer', $filtros['id_asignacion']);
        }
        if (!empty($filtros['id_ruta'])) {
            $query->where('id_ruta', $filtros['id_ruta']);
        }
        if (!empty($filtros['hora_inicio'])) {
            $query->whereDate('hora_inicio', $filtros['hora_inicio']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea una relación vehiculo_chofer_ruta.
     */
    public function crearVehiculoChoferRuta(array $data): VehiculoChoferRuta
    {
        return VehiculoChoferRuta::query()->create($data);
    }

    /**
     * Actualiza una relación vehiculo_chofer_ruta.
     */
    public function actualizarVehiculoChoferRuta(VehiculoChoferRuta $vcr, array $data): VehiculoChoferRuta
    {
        $vcr->update($data);
        return $vcr->fresh();
    }

    /**
     * Elimina (físicamente) una relación vehiculo_chofer_ruta.
     */
    public function eliminarVehiculoChoferRuta(VehiculoChoferRuta $vcr): void
    {
        $vcr->delete();
    }
}