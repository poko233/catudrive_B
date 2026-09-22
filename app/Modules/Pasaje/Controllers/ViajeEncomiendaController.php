<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Services\ViajeEncomiendaService;
use App\Shared\Security\SecurityResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ViajeEncomiendaController
{
    public function __construct(
        private readonly ViajeEncomiendaService $service,
    ) {
    }

    public function index(int $idViaje): JsonResponse
    {
        try {
            return response()->json($this->service->listar($idViaje));
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Viaje no encontrado.',
                'code' => 'NOT_FOUND',
            ], 404);
        }
    }
}
