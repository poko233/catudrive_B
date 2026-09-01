<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Permiso\Requests\StoreFormularioAccionRequest;
use App\Modules\Permiso\Requests\UpdateFormularioAccionRequest;
use App\Modules\Permiso\Resource\FormularioAccionResource;
use App\Modules\Permiso\Services\FormularioAccionService;
use App\Shared\Models\FormularioAccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FormularioAccionController extends Controller
{
    public function __construct(
        private readonly FormularioAccionService $service
    ) {
    }

    /**
     * GET /api/formulario_acciones
     */
    public function index(): AnonymousResourceCollection
    {
        return FormularioAccionResource::collection(
            $this->service->list()
        );
    }

    /**
     * POST /api/formulario_acciones
     */
    public function store(
        StoreFormularioAccionRequest $request
    ): JsonResponse {
        $regla =
            $this->service->store(
                $request->validated()
            );

        return response()->json([
            'data' =>
                new FormularioAccionResource(
                    $regla
                ),

            'message' =>
                'Regla de visibilidad creada correctamente.',
        ], 201);
    }

    /**
     * PATCH /api/formulario_acciones/{formularioAccion}
     */
    public function update(
        UpdateFormularioAccionRequest $request,
        FormularioAccion $formularioAccion
    ): JsonResponse {
        $regla =
            $this->service->update(
                $formularioAccion,
                $request->validated()
            );

        return response()->json([
            'data' =>
                new FormularioAccionResource(
                    $regla
                ),

            'message' =>
                'Regla de visibilidad actualizada correctamente.',
        ]);
    }

    /**
     * DELETE /api/formulario_acciones/{formularioAccion}
     */
    public function destroy(
        FormularioAccion $formularioAccion
    ): JsonResponse {
        $this->service->delete(
            $formularioAccion
        );

        return response()->json([
            'message' =>
                'Regla de visibilidad eliminada correctamente.',
        ]);
    }
}