<?php

declare(strict_types=1);

namespace App\Modules\Rol\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rol\Requests\SyncModulosRolRequest;
use App\Modules\Rol\Services\RolService;
use App\Shared\Models\Modulo;
use App\Shared\Models\Rol;
use Illuminate\Http\JsonResponse;

class ModuloRolController extends Controller
{
    public function __construct(
        private readonly RolService $service
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listado masivo de asignaciones
    |--------------------------------------------------------------------------
    |
    | GET /api/roles/modulos/asignaciones
    |
    | Evita:
    |
    | GET /roles
    | GET /roles/1/modulos
    | GET /roles/2/modulos
    | GET /roles/3/modulos
    | ...
    |
    */

    public function index(): JsonResponse
    {
        $roles =
            $this->service
                ->listarConModulos();

        $asignaciones =
            $roles
                ->flatMap(
                    static function (
                        Rol $rol
                    ) {
                        return $rol
                            ->modulos
                            ->map(
                                static fn (
                                    Modulo $modulo
                                ) => [
                                    'id_modulo' =>
                                        (int) $modulo->id,

                                    'id_rol' =>
                                        (int) $rol->id,

                                    'nombre_modulo' =>
                                        $modulo->modulo,

                                    'nombre_rol' =>
                                        $rol->rol,

                                    'icono_modulo' =>
                                        $modulo->icono,

                                    'descripcion_modulo' =>
                                        $modulo->descripcion,
                                ]
                            );
                    }
                )
                ->values();

        return response()->json([
            'data' =>
                $asignaciones,

            'message' =>
                'Asignaciones módulo-rol obtenidas correctamente.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Módulos de un rol
    |--------------------------------------------------------------------------
    */

    public function show(
        Rol $rol
    ): JsonResponse {
        $rol =
            $this->service
                ->obtenerConModulos(
                    $rol
                );

        return $this
            ->responseRolModulos(
                $rol,
                'Módulos del rol obtenidos correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar módulos
    |--------------------------------------------------------------------------
    */

    public function sync(
        SyncModulosRolRequest $request,
        Rol $rol
    ): JsonResponse {
        $rol =
            $this->service
                ->sincronizarModulos(
                    $rol,
                    $request->validated()[
                        'modulo_ids'
                    ]
                );

        return $this
            ->responseRolModulos(
                $rol,
                'Módulos del rol sincronizados correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Desasignar módulo
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Rol $rol,
        Modulo $modulo
    ): JsonResponse {
        $this->service
            ->desasignarModulo(
                $rol,
                $modulo
            );

        return response()->json([
            'message' =>
                'Módulo desasignado del rol correctamente.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Response individual rol ↔ módulos
    |--------------------------------------------------------------------------
    */

    private function responseRolModulos(
        Rol $rol,
        string $message
    ): JsonResponse {
        return response()->json([
            'data' => [
                'rol' => [
                    'id' =>
                        (int) $rol->id,

                    'rol' =>
                        $rol->rol,
                ],

                'modulos' =>
                    $rol
                        ->modulos
                        ->map(
                            static fn (
                                Modulo $modulo
                            ) => [
                                'id' =>
                                    (int) $modulo->id,

                                'modulo' =>
                                    $modulo->modulo,

                                'icono' =>
                                    $modulo->icono,

                                'descripcion' =>
                                    $modulo->descripcion,

                                'estado' =>
                                    $modulo->estado,
                            ]
                        )
                        ->values(),
            ],

            'message' =>
                $message,
        ]);
    }
}