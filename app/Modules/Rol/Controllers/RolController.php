<?php

declare(strict_types=1);

namespace App\Modules\Rol\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rol\Requests\StoreRolRequest;
use App\Modules\Rol\Requests\SyncPermisosRequest;
use App\Modules\Rol\Requests\UpdateRolRequest;
use App\Modules\Rol\Resource\RolResource;
use App\Modules\Rol\Services\RolService;
use App\Shared\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function __construct(
        private readonly RolService $service
    ) {
    }

    /**
     * GET /api/roles
     */
    public function index(Request $request)
    {
        $roles = $this->service->listar(
            $request->only([
                'estado',
                'buscar',
                'por_pagina',
            ])
        );

        return RolResource::collection(
            $roles
        );
    }

    /**
     * GET /api/roles/{rol}
     */
    public function show(
        Rol $rol
    ): RolResource {
        return new RolResource(
            $this->service->detalle(
                $rol
            )
        );
    }

    /**
     * POST /api/roles
     */
    public function store(
        StoreRolRequest $request
    ) {
        $rol = $this->service->crear(
            $request->validated()
        );

        return (new RolResource($rol))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT|PATCH /api/roles/{rol}
     */
    public function update(
        UpdateRolRequest $request,
        Rol $rol
    ): RolResource {
        return new RolResource(
            $this->service->actualizar(
                $rol,
                $request->validated()
            )
        );
    }

    /**
     * GET /api/roles/{rol}/permisos
     */
    public function getPermisos(
        Rol $rol
    ): RolResource {
        return new RolResource(
            $this->service->detalle(
                $rol
            )
        );
    }

    /**
     * PUT /api/roles/{rol}/permisos
     */
    public function syncPermisos(
        SyncPermisosRequest $request,
        Rol $rol
    ): RolResource {
        return new RolResource(
            $this->service->sincronizarPermisos(
                $rol,
                $request->validated()['permisos']
            )
        );
    }

    /**
     * DELETE /api/roles/{rol}
     */
    public function destroy(
        Rol $rol
    ): JsonResponse {
        $this->service->eliminar(
            $rol
        );

        return response()->json([
            'message' =>
                'Rol eliminado correctamente.',
        ]);
    }

    /**
     * GET /api/roles/permisos
     */
    public function todosConPermisos()
    {
        return RolResource::collection(
            $this->service->listarConPermisos()
        );
    }
}