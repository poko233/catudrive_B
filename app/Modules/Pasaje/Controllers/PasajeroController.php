<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\ListarPasajerosRequest;
use App\Modules\Pasaje\Requests\StorePasajeroRequest;
use App\Modules\Pasaje\Services\PasajeroService;
use Illuminate\Http\JsonResponse;

class PasajeroController
{
    public function __construct(
        private readonly PasajeroService $service,
    ) {
    }

    public function index(
        ListarPasajerosRequest $request
    ): JsonResponse {
        $pasajeros =
            $this->service
                ->listar($request->validated())
                ->map(
                    fn ($pasajero) =>
                        $this->service->serializar($pasajero)
                )
                ->values();

        return response()->json([
            'pasajeros' => $pasajeros,
        ]);
    }

    public function store(
        StorePasajeroRequest $request
    ): JsonResponse {
        $pasajero =
            $this->service
                ->crear($request->validated());

        return response()->json([
            'message' => 'Pasajero registrado correctamente.',
            'pasajero' => $this->service->serializar($pasajero),
        ], 201);
    }
}
