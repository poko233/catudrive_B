<?php

declare(strict_types=1);

namespace App\Modules\Ruta\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ruta\Requests\StoreRutaRequest;
use App\Modules\Ruta\Requests\UpdateRutaRequest;
use App\Modules\Ruta\Resource\RutaResource;
use App\Modules\Ruta\Services\RutaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RutaController extends Controller
{
    public function __construct(
        private readonly RutaService $service
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {
        $items =
            $this->service
                ->listar();

        return response()->json([
            'rutas' =>
                $items
                    ->map(
                        fn ($item) =>
                            (
                                new RutaResource(
                                    $item
                                )
                            )->resolve(
                                $request
                            )
                    )
                    ->values(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHOFERES CON VIAJES EN UNA RUTA
    |--------------------------------------------------------------------------
    */

    public function choferesViajes(
        int $ruta
    ): JsonResponse {
        $data =
            $this->service
                ->choferesConViajes(
                    $ruta
                );

        return response()->json(
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DETALLE
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $ruta
    ): JsonResponse {
        $item =
            $this->service
                ->obtener(
                    $ruta
                );

        return response()->json([
            'ruta' =>
                (
                    new RutaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreRutaRequest $request
    ): JsonResponse {
        $item =
            $this->service
                ->crear(
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Ruta registrada correctamente.',

            'ruta' =>
                (
                    new RutaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateRutaRequest $request,
        int $ruta
    ): JsonResponse {
        $item =
            $this->service
                ->actualizar(
                    $ruta,
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Ruta actualizada correctamente.',

            'ruta' =>
                (
                    new RutaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BAJA
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        int $ruta
    ): JsonResponse {
        $item =
            $this->service
                ->darBaja(
                    $ruta
                );

        return response()->json([
            'message' =>
                'Ruta dada de baja correctamente.',

            'ruta' =>
                (
                    new RutaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }
}
