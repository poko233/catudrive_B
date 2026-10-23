<?php

declare(strict_types=1);

namespace App\Modules\Sucursal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sucursal\Requests\StoreSucursalRequest;
use App\Modules\Sucursal\Requests\UpdateSucursalApiTokenRequest;   // ← NUEVO
use App\Modules\Sucursal\Requests\UpdateSucursalRequest;
use App\Modules\Sucursal\Resources\SucursalResource;
use App\Modules\Sucursal\Services\SucursalService;
use App\Shared\Models\Sucursal;
use App\Shared\Security\SecurityResponse;                          // ← NUEVO
use App\Shared\Services\ApiTokenCipherService;                     // ← NUEVO
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function __construct(
        private readonly SucursalService $service,
        private readonly ApiTokenCipherService $cipher,   // ← NUEVO
    ) {
    }

    /**
     * GET /api/sucursales/mis-sucursales
     *
     * Se utiliza antes de seleccionar una sucursal,
     * por lo que NO debe exigir middleware "sucursal".
     */
    public function misSucursales(
        Request $request
    ): JsonResponse {
        $sucursales =
            $this->service
                ->listarSucursalesUsuario(
                    $request->user()
                );

        return response()->json([
            'data' =>
                $sucursales
                    ->map(
                        fn(Sucursal $sucursal) => [
                            'id' =>
                                (int) $sucursal->id,

                            'id_empresa' =>
                                (int) $sucursal->id_empresa,

                            'sucursal' =>
                                $sucursal->sucursal,

                            'direccion' =>
                                $sucursal->direccion,

                            'ciudad' =>
                                $sucursal->ciudad,

                            'estado' =>
                                $sucursal->estado,

                            'imagen' =>
                                $sucursal->imagen,

                            'imagenUrl' =>
                                $this->service
                                    ->urlImagen(
                                        $sucursal->imagen
                                    ),
                        ]
                    )
                    ->values(),

            'message' =>
                'Sucursales obtenidas correctamente.',
        ]);
    }

    /**
     * GET /api/empresas/{idEmpresa}/sucursales
     */
    public function porEmpresa(
        int $idEmpresa
    ): JsonResponse {
        /*
         * La validación de que la empresa exista
         * queda ahora dentro del Service.
         */

        $sucursales =
            $this->service
                ->listarActivasPorEmpresa(
                    $idEmpresa
                );

        return response()->json([
            'data' =>
                $sucursales,

            'message' =>
                'Sucursales obtenidas correctamente.',
        ]);
    }

    /**
     * GET /api/sucursales
     */
    public function index(
        Request $request
    ) {
        $sucursales =
            $this->service
                ->listar(
                    $request->only([
                        'id_empresa',
                        'estado',
                        'buscar',
                        'por_pagina',
                    ])
                );

        return SucursalResource::collection(
            $sucursales
        );
    }

    /**
     * GET /api/sucursales/{sucursal}
     */
    public function show(
        Sucursal $sucursal
    ): SucursalResource {
        return new SucursalResource(
            $this->service
                ->obtenerDetalle(
                    $sucursal
                )
        );
    }

    /**
     * POST /api/sucursales
     */
    public function store(
        StoreSucursalRequest $request
    ) {
        $sucursal =
            $this->service
                ->crear(
                    $request->validated()
                );

        return (
            new SucursalResource(
                $sucursal
            )
        )
            ->response()
            ->setStatusCode(
                201
            );
    }

    /**
     * PUT|PATCH /api/sucursales/{sucursal}
     */
    public function update(
        UpdateSucursalRequest $request,
        Sucursal $sucursal
    ): SucursalResource {
        $sucursal =
            $this->service
                ->actualizar(
                    $sucursal,
                    $request->validated()
                );

        return new SucursalResource(
            $sucursal
        );
    }

    /**
     * DELETE /api/sucursales/{sucursal}
     */
    public function destroy(
        Sucursal $sucursal
    ): JsonResponse {
        $this->service
            ->eliminar(
                $sucursal
            );

        return response()->json([
            'message' =>
                'Sucursal eliminada correctamente.',
        ]);
    }
    /**
     * PATCH /api/sucursales/{sucursal}/api-token
     *
     * Body:
     *   { "api_token": "..." }  → cifra y guarda.
     *   { "api_token": null }   → limpia (NULL).
     *
     * Nunca devuelve el token en claro ni cifrado. Solo la máscara.
     */
    public function updateApiToken(
        UpdateSucursalApiTokenRequest $request,
        Sucursal $sucursal
    ): JsonResponse {
        $denied = $this->denegarSiNoPertenece($request, $sucursal);

        if ($denied !== null) {
            return $denied;
        }

        /** @var string|null $plaintext */
        $plaintext = $request->validated('api_token');

        $sucursal = $this->service->actualizarApiToken(
            $sucursal,
            $plaintext
        );

        $hasToken = !empty($sucursal->api_token);

        $masked = $hasToken
            ? $this->cipher->maskEncrypted((string) $sucursal->api_token)
            : null;

        return response()->json([
            'success' => true,

            'message' => $hasToken
                ? 'Token actualizado correctamente.'
                : 'Token eliminado correctamente.',

            'data' => [
                'has_api_token' => $hasToken,
                'api_token_masked' => $masked,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cierre de IDOR
    |--------------------------------------------------------------------------
    |
    | - Super-admin (rbac.super_roles) → bypass.
    | - Resto → debe pertenecer a la sucursal vía user_sucursal.
    | - Si no → 404 (no 403, para evitar enumeración cross-tenant).
    |
    */

    private function denegarSiNoPertenece(
        Request $request,
        Sucursal $sucursal
    ): ?JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return SecurityResponse::unauthenticated();
        }

        $superRoles = array_values(
            array_filter(
                (array) config('rbac.super_roles', []),
                static fn($role) =>
                    is_string($role) && trim($role) !== ''
            )
        );

        if (
            $superRoles !== [] &&
            $user->hasAnyRole($superRoles)
        ) {
            return null;
        }

        $pertenece = $user
            ->sucursales()
            ->where('sucursal.id', $sucursal->id)
            ->exists();

        if (!$pertenece) {
            return SecurityResponse::notFound(
                'Sucursal no encontrada.'
            );
        }

        return null;
    }
}