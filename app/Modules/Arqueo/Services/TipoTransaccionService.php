<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Services;

use App\Shared\Models\TipoTransaccion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class TipoTransaccionService
{
    public function listar(array $filtros, int $perPage = 15): LengthAwarePaginator
    {
        $query = TipoTransaccion::query()->orderBy('transaccion');

        if (!empty($filtros['tipo_transaccion'])) {
            $query->where('tipo_transaccion', $filtros['tipo_transaccion']);
        }

        if (!empty($filtros['buscar'])) {
            $buscar = (string) $filtros['buscar'];
            $query->where(function ($q) use ($buscar): void {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('transaccion', 'like', "%{$buscar}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function crear(array $datos): TipoTransaccion
    {
        $this->verificarCodigoUnico($datos['codigo']);

        return TipoTransaccion::query()->create([
            'codigo' => $datos['codigo'],
            'transaccion' => $datos['transaccion'],
            'tipo_transaccion' => $datos['tipo_transaccion'],
        ]);
    }

    public function actualizar(TipoTransaccion $tipo, array $datos): TipoTransaccion
    {
        if (isset($datos['codigo']) && $datos['codigo'] !== $tipo->codigo) {
            $this->verificarCodigoUnico($datos['codigo'], (int) $tipo->id);
        }

        $tipo->update($datos);

        return $tipo->fresh();
    }

    public function eliminar(TipoTransaccion $tipo): void
    {
        if ($tipo->ingresos()->exists() || $tipo->egresos()->exists()) {
            throw new RuntimeException(
                'No se puede eliminar el tipo de transacción: tiene movimientos asociados.'
            );
        }

        $tipo->delete();
    }

    private function verificarCodigoUnico(string $codigo, ?int $exceptoId = null): void
    {
        $query = TipoTransaccion::query()->where('codigo', $codigo);

        if ($exceptoId !== null) {
            $query->whereKeyNot($exceptoId);
        }

        if ($query->exists()) {
            throw new RuntimeException("El código '{$codigo}' ya está registrado.");
        }
    }
}