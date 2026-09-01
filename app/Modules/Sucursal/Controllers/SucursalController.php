<?php

declare(strict_types=1);

namespace App\Modules\Sucursal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sucursal\Requests\StoreSucursalRequest;
use App\Modules\Sucursal\Requests\UpdateSucursalRequest;
use App\Modules\Sucursal\Resources\SucursalResource;
use App\Modules\Sucursal\Services\SucursalService;
use App\Shared\Models\Sucursal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function __construct(
        private readonly SucursalService $service
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
                        fn (Sucursal $sucursal) => [
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
}