<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Services;

use App\Shared\Models\CategoriaVehiculo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CategoriaVehiculoService
{
    /**
     * Crea una nueva categoría de vehículo.
     *
     * @param array $data
     */
    public function store(array $data): CategoriaVehiculo
    {
        return CategoriaVehiculo::query()->create($data);
    }

    /**
     * Actualiza una categoría existente.
     *
     * @param CategoriaVehiculo $categoria
     * @param array $data
     */
    public function update(CategoriaVehiculo $categoria, array $data): CategoriaVehiculo
    {
        $categoria->update($data);

        return $categoria;
    }

    /**
     * Elimina una categoría solo si no tiene vehículos asociados.
     *
     * @param CategoriaVehiculo $categoria
     * @throws RuntimeException si hay vehículos vinculados.
     */
    public function destroy(CategoriaVehiculo $categoria): void
    {
        $tieneVehiculos = DB::table('vehiculo')
            ->where('id_categoria', $categoria->id)
            ->exists();

        if ($tieneVehiculos) {
            throw new RuntimeException(
                'No se puede eliminar la categoría porque tiene vehículos asociados.'
            );
        }

        $categoria->delete();
    }
}