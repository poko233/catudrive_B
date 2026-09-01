<?php

declare(strict_types=1);

namespace App\Modules\RecursosHumanos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecursosHumanos\Requests\ActualizarFotoUsuarioRequest;
use App\Modules\RecursosHumanos\Requests\ActualizarUsuarioRequest;
use App\Modules\RecursosHumanos\Resource\UsuarioResource;
use App\Modules\RecursosHumanos\Services\RecursosHumanosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecursosHumanosController extends Controller
{
    public function __construct(
        private readonly RecursosHumanosService $service
    ) {
    }

    /**
     * GET /api/recursos-humanos/usuarios
     */
    public function index(Request $request): JsonResponse
    {
        $usuarios = $this->service->listarUsuarios();

        return response()->json([
            'usuarios' => $usuarios
                ->map(
                    fn ($usuario) =>
                        (new UsuarioResource($usuario))
                            ->resolve($request)
                )
                ->values(),
        ]);
    }

    /**
     * GET /api/recursos-humanos/usuarios/{id}
     */
    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $usuario =
            $this->service
                ->obtenerUsuario(
                    $id
                );

        return response()->json([
            'usuario' =>
                (new UsuarioResource($usuario))
                    ->resolve($request),
        ]);
    }

    /**
     * PUT|PATCH /api/recursos-humanos/usuarios/{id}
     */
    public function update(
        ActualizarUsuarioRequest $request,
        int $id
    ): JsonResponse {
        $usuario =
            $this->service
                ->actualizarUsuario(
                    $id,
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Usuario actualizado correctamente.',

            'usuario' =>
                (new UsuarioResource($usuario))
                    ->resolve($request),
        ]);
    }

    /**
     * POST /api/recursos-humanos/usuarios/{id}/foto
     */
    public function updatePhoto(
        ActualizarFotoUsuarioRequest $request,
        int $id
    ): JsonResponse {
        $usuario =
            $this->service
                ->actualizarFoto(
                    $id,
                    $request->file('foto')
                );

        return response()->json([
            'message' =>
                'Foto actualizada correctamente.',

            'usuario' =>
                (new UsuarioResource($usuario))
                    ->resolve($request),
        ]);
    }
}
