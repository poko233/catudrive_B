<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\StoreVehiculoChoferRutaRequest;
use App\Modules\Pasaje\Requests\UpdateVehiculoChoferRutaRequest;
use App\Modules\Pasaje\Resources\VehiculoChoferRutaResource;
use App\Modules\Pasaje\Services\TransporteService;
use App\Shared\Models\VehiculoChoferRuta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehiculoChoferRutaController
{
    public function __construct(
        private readonly TransporteService $transporteService,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $vcr = $this->transporteService->listarVehiculoChoferRuta(
            request()->only(['id_asignacion', 'id_ruta', 'hora_inicio']),
            (int) request()->input('per_page', 15)
        );

        return VehiculoChoferRutaResource::collection($vcr);
    }

    public function store(StoreVehiculoChoferRutaRequest $request): VehiculoChoferRutaResource
    {
        $vcr = $this->transporteService->crearVehiculoChoferRuta($request->validated());

        return new VehiculoChoferRutaResource($vcr);
    }

    public function show(VehiculoChoferRuta $vcr): VehiculoChoferRutaResource
    {
        $vcr->load([
            'asignacion.chofer.usuario',
            'asignacion.vehiculo',
            'ruta',
        ]);

        return new VehiculoChoferRutaResource($vcr);
    }

    public function update(UpdateVehiculoChoferRutaRequest $request, VehiculoChoferRuta $vcr): VehiculoChoferRutaResource
    {
        $vcr = $this->transporteService->actualizarVehiculoChoferRuta($vcr, $request->validated());

        return new VehiculoChoferRutaResource($vcr);
    }

    public function destroy(VehiculoChoferRuta $vcr): JsonResponse
    {
        $this->transporteService->eliminarVehiculoChoferRuta($vcr);

        return response()->json([
            'success' => true,
            'message' => 'Relación vehículo-chofer-ruta eliminada correctamente.',
        ]);
    }
}