<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\StoreVehiculoChoferRutaRequest;
use App\Modules\Pasaje\Requests\UpdateVehiculoChoferRutaRequest;
use App\Modules\Pasaje\Resources\VehiculoChoferRutaResource;
use App\Modules\Pasaje\Services\TransporteService;
use App\Shared\Models\VehiculoChoferRuta;
use App\Shared\Security\SecurityResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VehiculoChoferRutaController
{
    public function __construct(
        private readonly TransporteService $transporteService,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return VehiculoChoferRutaResource::collection(
            $this->transporteService->listarVehiculoChoferRuta(
                request()->only(['id_asignacion', 'id_ruta', 'hora_inicio']),
                (int) request()->input('per_page', 15)
            )
        );
    }

    public function store(StoreVehiculoChoferRutaRequest $request): JsonResponse|VehiculoChoferRutaResource
    {
        try {
            return new VehiculoChoferRutaResource(
                $this->transporteService->crearVehiculoChoferRuta($request->validated())
            );
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        }
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

    public function update(UpdateVehiculoChoferRutaRequest $request, VehiculoChoferRuta $vcr): JsonResponse|VehiculoChoferRutaResource
    {
        try {
            return new VehiculoChoferRutaResource(
                $this->transporteService->actualizarVehiculoChoferRuta($vcr, $request->validated())
            );
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        }
    }

    public function destroy(VehiculoChoferRuta $vcr): JsonResponse
    {
        try {
            $this->transporteService->eliminarVehiculoChoferRuta($vcr);
            return response()->json(['success' => true, 'message' => 'Relación vehículo-chofer-ruta eliminada correctamente.']);
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        }
    }
}