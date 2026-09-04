<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Controllers;

use App\Modules\Vehiculo\Requests\StoreVehiculoRequest;
use App\Modules\Vehiculo\Requests\UpdateVehiculoRequest;
use App\Modules\Vehiculo\Resource\VehiculoResource;
use App\Modules\Vehiculo\Services\VehiculoService;
use App\Shared\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehiculoController
{
    public function __construct(
        private readonly VehiculoService $vehiculoService,
    ) {
    }

    /**
     * Lista todos los vehículos (con relaciones).
     */
    public function index(): AnonymousResourceCollection
    {
        $vehiculos = Vehiculo::query()
            ->with([
                'categoria',
                'pisos' => function ($q) {
                    $q->orderBy('orden')->orderBy('numero');
                },
                'pisos.asientos' => function ($q) {
                    $q->orderBy('fila')->orderBy('columna');
                }
            ])
            ->get();

        return VehiculoResource::collection($vehiculos);
    }

    /**
     * Crea un vehículo completo.
     */
    public function store(StoreVehiculoRequest $request): VehiculoResource
    {
        $vehiculo = $this->vehiculoService->store($request->validated());

        return new VehiculoResource($vehiculo);
    }

    /**
     * Muestra un vehículo específico.
     */
    public function show(Vehiculo $vehiculo): VehiculoResource
    {
        $vehiculo->load([
            'categoria',
            'pisos' => function ($q) {
                $q->orderBy('orden')->orderBy('numero');
            },
            'pisos.asientos' => function ($q) {
                $q->orderBy('fila')->orderBy('columna');
            },
        ]);

        return new VehiculoResource($vehiculo);
    }

    /**
     * Actualiza un vehículo y su plano.
     */
    public function update(UpdateVehiculoRequest $request, Vehiculo $vehiculo): VehiculoResource
    {
        $vehiculo = $this->vehiculoService->update($vehiculo, $request->validated());

        return new VehiculoResource($vehiculo);
    }

    /**
     * Da de baja un vehículo (no elimina físicamente).
     */
    public function destroy(Vehiculo $vehiculo): JsonResponse
    {
        $this->vehiculoService->destroy($vehiculo);

        return response()->json([
            'success' => true,
            'message' => 'Vehículo dado de baja correctamente.',
        ]);
    }
}