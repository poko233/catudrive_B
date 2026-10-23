<?php

declare(strict_types=1);

namespace App\Modules\Empresa\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Empresa\Requests\StoreEmpresaRequest;
use App\Modules\Empresa\Requests\UpdateEmpresaRequest;
use App\Modules\Empresa\Requests\UploadImagenRequest;
use App\Modules\Empresa\Resources\EmpresaResource;
use App\Modules\Sucursal\Resources\SucursalConApiTokenResource;   // ← NUEVO
use App\Modules\Sucursal\Services\SucursalService;                 // ← NUEVO
use App\Modules\Empresa\Services\EmpresaService;
use App\Shared\Models\Empresa;
use App\Shared\Security\SecurityResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EmpresaController extends Controller
{
    public function __construct(
        private readonly EmpresaService $service,
        private readonly SucursalService $sucursalService,
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

        /*
         * IMPORTANTE: no exponer api_token en el listado.
         * El Resource usa exposeApiToken=false por defecto.
         */

        return EmpresaResource::collection(
            $empresas
        );
    }

    /**
     * GET /api/empresa/{empresa}
     */
    public function show(
        Request $request,
        Empresa $empresa
    ): EmpresaResource|JsonResponse {
        $denied =
            $this->denegarSiNoPertenece(
                $request,
                $empresa
            );

        if ($denied !== null) {
            return $denied;
        }

        return new EmpresaResource(
            $empresa,
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
    ): EmpresaResource|JsonResponse {
        $denied =
            $this->denegarSiNoPertenece(
                $request,
                $empresa
            );

        if ($denied !== null) {
            return $denied;
        }

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
        Request $request,
        Empresa $empresa
    ): JsonResponse {
        $denied =
            $this->denegarSiNoPertenece(
                $request,
                $empresa
            );

        if ($denied !== null) {
            return $denied;
        }

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
        $denied =
            $this->denegarSiNoPertenece(
                $request,
                $empresa
            );

        if ($denied !== null) {
            return $denied;
        }

        try {
            $empresa = $this
                ->service
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
        } catch (InvalidArgumentException $exception) {
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
        Request $request,
        Empresa $empresa,
        string $tipo
    ): JsonResponse {
        $denied =
            $this->denegarSiNoPertenece(
                $request,
                $empresa
            );

        if ($denied !== null) {
            return $denied;
        }

        try {
            $empresa = $this
                ->service
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
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' =>
                    $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/empresa/mi-empresa
     *
     * Devuelve:
     *   - data.empresa     → datos de la empresa resuelta (usuario o
     *                        fallback a la primera activa).
     *   - data.sucursales  → sucursales del usuario dentro de esa
     *                        empresa, cada una con su api_token
     *                        cifrado (base62) + máscara.
     */
    public function miEmpresa(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $empresa = $this->service
            ->resolverEmpresaParaMiEmpresa($user);

        if (!$empresa) {
            return SecurityResponse::notFound(
                'No se encontró información de empresa.'
            );
        }

        $sucursales = $this->sucursalService
            ->listarSucursalesUsuarioConApiToken(
                $user,
                (int) $empresa->id
            );

        return response()->json([
            'data' => [
                'empresa' => (new EmpresaResource($empresa))
                    ->resolve($request),

                'sucursales' => SucursalConApiTokenResource::collection(
                    $sucursales
                )->resolve($request),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cierre de IDOR
    |--------------------------------------------------------------------------
    |
    | - Super-admin (rbac.super_roles) → bypass.
    | - Resto → debe pertenecer a la empresa vía user_sucursal.
    | - Si no → 404 (no 403, para evitar enumeración cross-tenant).
    |
    | Devuelve JsonResponse si hay que cortar, null si puede continuar.
    |
    */

    private function denegarSiNoPertenece(
        Request $request,
        Empresa $empresa
    ): ?JsonResponse {
        $user =
            $request->user();

        if ($user === null) {
            return SecurityResponse::unauthenticated();
        }

        $superRoles =
            array_values(
                array_filter(
                    (array) config(
                        'rbac.super_roles',
                        []
                    ),
                    static fn($role) =>
                        is_string($role)
                        && trim($role) !== ''
                )
            );

        if (
            $superRoles !== [] &&
            $user->hasAnyRole($superRoles)
        ) {
            return null;
        }

        if (
            !$this->service->usuarioPerteneceAEmpresa(
                $user,
                $empresa
            )
        ) {
            return SecurityResponse::notFound(
                'Empresa no encontrada.'
            );
        }

        return null;
    }
}