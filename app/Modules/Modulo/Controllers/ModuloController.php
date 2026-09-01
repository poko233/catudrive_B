<?php

declare(strict_types=1);

namespace App\Modules\Modulo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Modulo\Requests\ModuloRequest;
use App\Modules\Modulo\Requests\ReordenarModulosRequest;
use App\Modules\Modulo\Resource\ModuloResource;
use App\Modules\Modulo\Services\ModuloService;
use App\Shared\Models\Modulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModuloController extends Controller
{
    public function __construct(
        private readonly ModuloService $moduloService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/modulos
    |--------------------------------------------------------------------------
    */

    public function index(): AnonymousResourceCollection
    {
        return ModuloResource::collection(
            $this->moduloService
                ->listar()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/modulos
    |--------------------------------------------------------------------------
    */

    public function store(
        ModuloRequest $request
    ) {
        $modulo =
            $this->moduloService
                ->crear(
                    $request->validated()
                );

        return (
            new ModuloResource(
                $modulo
            )
        )
            ->response()
            ->setStatusCode(
                201
            );
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/modulos/orden
    |--------------------------------------------------------------------------
    */

    public function reorder(
        ReordenarModulosRequest $request
    ): AnonymousResourceCollection {
        $modulos =
            $this->moduloService
                ->reordenar(
                    $request
                        ->validated()[
                            'modulo_ids'
                        ]
                );

        return ModuloResource::collection(
            $modulos
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/modulos/{modulo}
    |--------------------------------------------------------------------------
    */

    public function show(
        Modulo $modulo
    ): ModuloResource {
        return new ModuloResource(
            $modulo->load(
                'formularios'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PUT|PATCH /api/modulos/{modulo}
    |--------------------------------------------------------------------------
    */

    public function update(
        ModuloRequest $request,
        Modulo $modulo
    ): ModuloResource {
        $modulo =
            $this->moduloService
                ->actualizar(
                    $modulo,
                    $request->validated()
                );

        return new ModuloResource(
            $modulo
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/modulos/{modulo}
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Modulo $modulo
    ): JsonResponse {
        $this->moduloService
            ->eliminar(
                $modulo
            );

        return response()->json([
            'message' =>
                'Módulo eliminado correctamente.',
        ]);
    }
}