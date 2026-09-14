<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\AsignacionVehiculoChofer;
use App\Shared\Models\Ruta;
use App\Shared\Models\VehiculoChoferRuta;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TransporteService
{
    public function __construct(
        private readonly ChoferContextService $choferContext,
    ) {
    }

    public function listarAsignaciones(array $filtros, int $perPage = 15): LengthAwarePaginator
    {
        $idChofer = $this->choferContext->idChoferActual();

        $query = AsignacionVehiculoChofer::query()
            ->with(['chofer.usuario', 'vehiculo'])
            ->orderBy('created_at', 'desc');

        if ($idChofer !== null) {
            $query->where('id_chofer', $idChofer);
        }

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

    public function crearAsignacion(array $data): AsignacionVehiculoChofer
    {
        $this->bloquearEscrituraChofer();
        return AsignacionVehiculoChofer::query()->create($data);
    }

    public function actualizarAsignacion(AsignacionVehiculoChofer $asignacion, array $data): AsignacionVehiculoChofer
    {
        $this->bloquearEscrituraChofer();
        $asignacion->update($data);
        return $asignacion->fresh();
    }

    public function eliminarAsignacion(AsignacionVehiculoChofer $asignacion): void
    {
        $this->bloquearEscrituraChofer();
        $asignacion->delete();
    }

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

    public function listarVehiculoChoferRuta(array $filtros, int $perPage = 15)
    {
        $idChofer = $this->choferContext->idChoferActual();

        $query = VehiculoChoferRuta::query()
            ->with([
                'asignacion.chofer.usuario',
                'asignacion.vehiculo',
                'ruta',
            ])
            ->orderBy('created_at', 'desc');

        if ($idChofer !== null) {
            $query->whereHas('asignacion', fn($q) => $q->where('id_chofer', $idChofer));
        }

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

    public function crearVehiculoChoferRuta(array $data): VehiculoChoferRuta
    {
        $this->bloquearEscrituraChofer();
        return VehiculoChoferRuta::query()->create($data);
    }

    public function actualizarVehiculoChoferRuta(VehiculoChoferRuta $vcr, array $data): VehiculoChoferRuta
    {
        $this->bloquearEscrituraChofer();
        $vcr->update($data);
        return $vcr->fresh();
    }

    public function eliminarVehiculoChoferRuta(VehiculoChoferRuta $vcr): void
    {
        $this->bloquearEscrituraChofer();
        $vcr->delete();
    }

    private function bloquearEscrituraChofer(): void
    {
        if ($this->choferContext->esChofer()) {
            throw new AccessDeniedHttpException(
                'No tienes permiso para realizar esta operación.'
            );
        }
    }
}