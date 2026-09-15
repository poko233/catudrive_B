<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\CrearViajeRequest;
use App\Modules\Pasaje\Requests\ListarViajesRequest;
use App\Modules\Pasaje\Requests\UpdateEstadoViajeRequest;
use App\Modules\Pasaje\Resources\ViajeResource;
use App\Modules\Pasaje\Services\VentaService;
use App\Shared\Models\Viaje;
use App\Shared\Security\SecurityResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ViajeController
{
    public function __construct(
        private readonly VentaService $ventaService,
    ) {
    }

    public function index(ListarViajesRequest $request): AnonymousResourceCollection
    {
        return ViajeResource::collection($this->ventaService->listarViajes(
            $request->validated(),
            (int) $request->input('per_page', 15)
        ));
    }

    public function store(CrearViajeRequest $request): JsonResponse|ViajeResource
    {
        try {
            return new ViajeResource($this->ventaService->crearViaje(
                (int) $request->validated()['id_vehiculo_chofer_ruta']
            ));
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Asignación no encontrada.', 'code' => 'NOT_FOUND'], 404);
        }
    }

    public function updateEstado(UpdateEstadoViajeRequest $request, Viaje $viaje): JsonResponse|ViajeResource
    {
        try {
            return new ViajeResource($this->ventaService->actualizarEstadoViaje(
                $viaje,
                $request->validated()['estado']
            ));
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        }
    }
}