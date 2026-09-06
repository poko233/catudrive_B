<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\CrearViajeRequest;
use App\Modules\Pasaje\Requests\ListarViajesRequest;
use App\Modules\Pasaje\Requests\UpdateEstadoViajeRequest;
use App\Modules\Pasaje\Resources\ViajeResource;
use App\Modules\Pasaje\Services\VentaService;
use App\Shared\Models\Viaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ViajeController
{
    public function __construct(
        private readonly VentaService $ventaService,
    ) {
    }

    public function index(ListarViajesRequest $request): AnonymousResourceCollection
    {
        $viajes = $this->ventaService->listarViajes(
            $request->validated(),
            (int) $request->input('per_page', 15)
        );

        return ViajeResource::collection($viajes);
    }

    public function store(CrearViajeRequest $request): ViajeResource
    {
        $viaje = $this->ventaService->crearViaje(
            (int) $request->validated()['id_vehiculo_chofer_ruta']
        );

        return new ViajeResource($viaje);
    }
    public function updateEstado(
        UpdateEstadoViajeRequest $request,
        Viaje $viaje
    ): ViajeResource {
        $viaje = $this->ventaService->actualizarEstadoViaje(
            $viaje,
            $request->validated()['estado']
        );

        return new ViajeResource($viaje);
    }
}