<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Controllers;

use App\Modules\Vehiculo\Requests\StoreCategoriaVehiculoRequest;
use App\Modules\Vehiculo\Requests\UpdateCategoriaVehiculoRequest;
use App\Modules\Vehiculo\Resource\CategoriaVehiculoResource;
use App\Modules\Vehiculo\Services\CategoriaVehiculoService;
use App\Shared\Models\CategoriaVehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class CategoriaVehiculoController
{
    public function __construct(
        private readonly CategoriaVehiculoService $categoriaService,
    ) {
    }

    /**
     * Lista todas las categorías de vehículo.
     */
    public function index(): AnonymousResourceCollection
    {
        $categorias = CategoriaVehiculo::query()
            ->orderBy('categoria')
            ->get();

        return CategoriaVehiculoResource::collection($categorias);
    }

    /**
     * Crea una nueva categoría.
     */
    public function store(StoreCategoriaVehiculoRequest $request): CategoriaVehiculoResource
    {
        $categoria = $this->categoriaService->store($request->validated());

        return new CategoriaVehiculoResource($categoria);
    }

    /**
     * Muestra una categoría específica.
     */
    public function show(CategoriaVehiculo $categoriaVehiculo): CategoriaVehiculoResource
    {
        return new CategoriaVehiculoResource($categoriaVehiculo);
    }

    /**
     * Actualiza una categoría.
     */
    public function update(
        UpdateCategoriaVehiculoRequest $request,
        CategoriaVehiculo $categoriaVehiculo,
    ): CategoriaVehiculoResource {
        $categoria = $this->categoriaService->update(
            $categoriaVehiculo,
            $request->validated(),
        );

        return new CategoriaVehiculoResource($categoria);
    }

    /**
     * Elimina una categoría si no está en uso.
     */
    public function destroy(CategoriaVehiculo $categoriaVehiculo): JsonResponse
    {
        try {
            $this->categoriaService->destroy($categoriaVehiculo);

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }
}