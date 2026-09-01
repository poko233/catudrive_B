<?php

declare(strict_types=1);

namespace App\Modules\Formulario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Formulario\Requests\FormularioRequest;
use App\Modules\Formulario\Resource\FormularioResource;
use App\Modules\Formulario\Services\FormularioService;
use App\Shared\Models\Formulario;
use Illuminate\Http\JsonResponse;

class FormularioController extends Controller
{
    public function __construct(
        private readonly FormularioService $formularioService
    ) {
    }

    /**
     * GET /api/formularios
     */
    public function index()
    {
        return FormularioResource::collection(
            $this->formularioService->listar()
        );
    }

    /**
     * POST /api/formularios
     */
    public function store(
        FormularioRequest $request
    ) {
        $formulario =
            $this->formularioService
                ->crear(
                    $request->validated()
                );

        return (
            new FormularioResource(
                $formulario
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/formularios/{formulario}
     */
    public function show(
        Formulario $formulario
    ): FormularioResource {
        return new FormularioResource(
            $formulario->load(
                'modulos'
            )
        );
    }

    /**
     * PUT|PATCH /api/formularios/{formulario}
     */
    public function update(
        FormularioRequest $request,
        Formulario $formulario
    ): FormularioResource {
        $formulario =
            $this->formularioService
                ->actualizar(
                    $formulario,
                    $request->validated()
                );

        return new FormularioResource(
            $formulario
        );
    }

    /**
     * DELETE /api/formularios/{formulario}
     */
    public function destroy(
        Formulario $formulario
    ): JsonResponse {
        $this->formularioService
            ->eliminar(
                $formulario
            );

        return response()->json([
            'message' =>
                'Formulario eliminado correctamente.',
        ]);
    }
}