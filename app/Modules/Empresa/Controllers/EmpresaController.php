<?php

declare(strict_types=1);

namespace App\Modules\Empresa\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Empresa\Requests\StoreEmpresaRequest;
use App\Modules\Empresa\Requests\UpdateEmpresaRequest;
use App\Modules\Empresa\Requests\UploadImagenRequest;
use App\Modules\Empresa\Resources\EmpresaResource;
use App\Modules\Empresa\Services\EmpresaService;
use App\Shared\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EmpresaController extends Controller
{
    public function __construct(
        private readonly EmpresaService $service
    ) {
    }

    /**
     * GET /api/empresa
     */
    public function index(Request $request)
    {
        $empresas = $this->service->listar(
            $request->only([
                'estado',
                'buscar',
                'por_pagina',
            ])
        );

        return EmpresaResource::collection(
            $empresas
        );
    }

    /**
     * GET /api/empresa/{empresa}
     */
    public function show(
        Empresa $empresa
    ): EmpresaResource {
        return new EmpresaResource(
            $empresa
        );
    }

    /**
     * POST /api/empresa
     */
    public function store(
        StoreEmpresaRequest $request
    ) {
        $empresa = $this->service->crear(
            $request->validated()
        );

        return (new EmpresaResource(
            $empresa
        ))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT|PATCH /api/empresa/{empresa}
     */
    public function update(
        UpdateEmpresaRequest $request,
        Empresa $empresa
    ): EmpresaResource {
        $empresa = $this->service->actualizar(
            $empresa,
            $request->validated()
        );

        return new EmpresaResource(
            $empresa
        );
    }

    /**
     * DELETE /api/empresa/{empresa}
     */
    public function destroy(
        Empresa $empresa
    ): JsonResponse {
        $this->service->eliminar(
            $empresa
        );

        return response()->json([
            'message' =>
                'Empresa eliminada correctamente.',
        ]);
    }

    /**
     * POST /api/empresa/{empresa}/imagen/{tipo}
     */
    public function uploadImagen(
        UploadImagenRequest $request,
        Empresa $empresa,
        string $tipo
    ): JsonResponse {
        try {
            $empresa =
                $this->service
                    ->actualizarImagen(
                        $empresa,
                        $tipo,
                        $request->file('imagen')
                    );

            return response()->json([
                'message' =>
                    'Imagen actualizada correctamente.',

                'data' =>
                    new EmpresaResource(
                        $empresa
                    ),
            ]);
        } catch (
            InvalidArgumentException $exception
        ) {
            return response()->json([
                'message' =>
                    $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * DELETE /api/empresa/{empresa}/imagen/{tipo}
     */
    public function deleteImagen(
        Empresa $empresa,
        string $tipo
    ): JsonResponse {
        try {
            $empresa =
                $this->service
                    ->eliminarImagen(
                        $empresa,
                        $tipo
                    );

            return response()->json([
                'message' =>
                    'Imagen eliminada correctamente.',

                'data' =>
                    new EmpresaResource(
                        $empresa
                    ),
            ]);
        } catch (
            InvalidArgumentException $exception
        ) {
            return response()->json([
                'message' =>
                    $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/empresa/mi-empresa
     */
    public function miEmpresa():
        EmpresaResource|JsonResponse
    {
        $empresa =
            $this->service
                ->obtenerEmpresaPrincipal();

        if (!$empresa) {
            return response()->json([
                'message' =>
                    'No se encontró información de empresa.',
            ], 404);
        }

        return new EmpresaResource(
            $empresa
        );
    }
}