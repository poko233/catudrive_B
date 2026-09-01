<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Permiso\Requests\PermisoRequest;
use App\Modules\Permiso\Requests\StorePermisoRequest;
use App\Modules\Permiso\Resource\PermisoResource;
use App\Modules\Permiso\Services\PermisoService;
use Illuminate\Http\JsonResponse;

class PermisoController extends Controller
{
    public function __construct(
        private readonly PermisoService $permisoService
    ) {
    }

    /**
     * GET /api/permisos/{idRol}
     */
    public function index(int $idRol): JsonResponse
    {
        $permisos = $this->permisoService
            ->getPermisosByRol($idRol);

        return response()->json([
            'data' => PermisoResource::collection($permisos),
            'message' => 'Permisos obtenidos correctamente.',
        ]);
    }

    /**
     * POST /api/permisos/{idRol}
     */
    public function addPermiso(
        StorePermisoRequest $request,
        int $idRol
    ): JsonResponse {
        $permiso = $this->permisoService->addPermiso(
            $idRol,
            $request->validated()
        );

        return response()->json([
            'data' => new PermisoResource($permiso),
            'message' => 'Permiso agregado correctamente.',
        ], 201);
    }

    /**
     * POST /api/permisos/{idRol}/sync
     */
    public function sync(
        PermisoRequest $request,
        int $idRol
    ): JsonResponse {
        $permisos = $this->permisoService->syncPermisos(
            $idRol,
            $request->validated()['permisos']
        );

        return response()->json([
            'data' => PermisoResource::collection($permisos),
            'message' => 'Permisos sincronizados correctamente.',
        ]);
    }

    /**
     * DELETE /api/permisos/{rolId}/{formularioId}/{accionId}
     *
     * Conserva el contrato actual: elimina esa acción para el
     * formulario del rol, independientemente del módulo.
     */
    public function destroy(
        int $rolId,
        int $formularioId,
        int $accionId
    ): JsonResponse {
        $eliminados = $this->permisoService->removeByParams(
            $rolId,
            $formularioId,
            $accionId
        );

        if ($eliminados === 0) {
            return response()->json([
                'message' => 'No se encontró el permiso solicitado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Permiso eliminado correctamente.',
        ]);
    }
}
