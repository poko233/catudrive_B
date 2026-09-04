<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Encomienda\Requests\AsignarEncomiendaRequest;
use App\Modules\Encomienda\Requests\EntregarEncomiendaRequest;
use App\Modules\Encomienda\Requests\StoreEncomiendaRequest;
use App\Modules\Encomienda\Requests\UpdateEncomiendaRequest;
use App\Modules\Encomienda\Resource\EncomiendaResource;
use App\Modules\Encomienda\Services\EncomiendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EncomiendaController extends Controller
{
    public function __construct(
        private readonly EncomiendaService $service
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
            'encomiendas' =>
                $items
                    ->map(
                        fn ($item) =>
                            (
                                new EncomiendaResource(
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

    public function catalogos(): JsonResponse
    {
        return response()->json(
            $this->service
                ->catalogos()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUSCAR POR GUÍA
    |--------------------------------------------------------------------------
    */

    public function buscarPorGuia(
        Request $request,
        string $guia
    ): JsonResponse {
        $item =
            $this->service
                ->buscarPorGuia(
                    $guia
                );

        return response()->json([
            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DETALLE
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->obtener(
                    $encomienda
                );

        return response()->json([
            'encomienda' =>
                (
                    new EncomiendaResource(
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
        StoreEncomiendaRequest $request
    ): JsonResponse {
        $item =
            $this->service
                ->crear(
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Encomienda registrada correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
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
        UpdateEncomiendaRequest $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->actualizar(
                    $encomienda,
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Encomienda actualizada correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ASIGNAR
    |--------------------------------------------------------------------------
    */

    public function asignar(
        AsignarEncomiendaRequest $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->asignar(
                    $encomienda,
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Encomienda asignada al viaje correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ENTREGAR
    |--------------------------------------------------------------------------
    */

    public function entregar(
        EntregarEncomiendaRequest $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->entregar(
                    $encomienda
                );

        return response()->json([
            'message' =>
                'Encomienda entregada correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ANULAR
    |--------------------------------------------------------------------------
    */

    public function anular(
        Request $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->anular(
                    $encomienda
                );

        return response()->json([
            'message' =>
                'Encomienda anulada correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }
}