<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AsignacionVehiculo\Requests\CambiarAsignacionVehiculoRequest;
use App\Modules\AsignacionVehiculo\Requests\FinalizarAsignacionVehiculoRequest;
use App\Modules\AsignacionVehiculo\Requests\StoreAsignacionVehiculoRequest;
use App\Modules\AsignacionVehiculo\Resource\AsignacionVehiculoResource;
use App\Modules\AsignacionVehiculo\Services\AsignacionVehiculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsignacionVehiculoController extends Controller
{
    public function __construct(
        private readonly AsignacionVehiculoService $service
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
            'asignaciones' =>
                $items
                    ->map(
                        fn ($item) =>
                            (
                                new AsignacionVehiculoResource(
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
    | CATÁLOGOS
    |--------------------------------------------------------------------------
    */

    public function catalogos(
        Request $request
    ): JsonResponse {
        $id =
            $request->integer(
                'asignacion_id'
            );

        return response()->json(
            $this->service
                ->catalogos(
                    $id > 0
                        ? $id
                        : null
                )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORIAL
    |--------------------------------------------------------------------------
    */

    public function historial(
        Request $request
    ): JsonResponse {
        $items =
            $this->service
                ->historial();

        return response()->json([
            'asignaciones' =>
                $items
                    ->map(
                        fn ($item) =>
                            (
                                new AsignacionVehiculoResource(
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
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $asignacion
    ): JsonResponse {
        $item =
            $this->service
                ->obtener(
                    $asignacion
                );

        return response()->json([
            'asignacion' =>
                (
                    new AsignacionVehiculoResource(
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
        StoreAsignacionVehiculoRequest $request
    ): JsonResponse {
        $item =
            $this->service
                ->crear(
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Vehículo asignado correctamente.',

            'asignacion' =>
                (
                    new AsignacionVehiculoResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | CAMBIAR
    |--------------------------------------------------------------------------
    */

    public function cambiar(
        CambiarAsignacionVehiculoRequest $request,
        int $asignacion
    ): JsonResponse {
        $resultado =
            $this->service
                ->cambiar(
                    $asignacion,
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Cambio de asignación realizado correctamente.',

            'anterior' =>
                (
                    new AsignacionVehiculoResource(
                        $resultado[
                            'anterior'
                        ]
                    )
                )->resolve(
                    $request
                ),

            'asignacion' =>
                (
                    new AsignacionVehiculoResource(
                        $resultado[
                            'asignacion'
                        ]
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FINALIZAR
    |--------------------------------------------------------------------------
    */

    public function finalizar(
        FinalizarAsignacionVehiculoRequest $request,
        int $asignacion
    ): JsonResponse {
        $item =
            $this->service
                ->finalizar(
                    $asignacion,

                    $request->validated()[
                        'fecha_finalizacion'
                    ]
                );

        return response()->json([
            'message' =>
                'Asignación finalizada correctamente.',

            'asignacion' =>
                (
                    new AsignacionVehiculoResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }
}