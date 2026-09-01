<?php

declare(strict_types=1);

namespace App\Modules\Modulo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Modulo\Requests\SyncFormulariosModuloRequest;
use App\Modules\Modulo\Services\ModuloService;
use App\Shared\Models\Formulario;
use App\Shared\Models\Modulo;
use Illuminate\Http\JsonResponse;

class FormularioModuloController extends Controller
{
    public function __construct(
        private readonly ModuloService $moduloService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listado masivo de asignaciones
    |--------------------------------------------------------------------------
    |
    | GET /api/modulos/formularios/asignaciones
    |
    | Evita:
    |
    | GET /modulos
    | GET /modulos/1/formularios
    | GET /modulos/2/formularios
    | GET /modulos/3/formularios
    | ...
    |
    | ModuloService::listar() ya utiliza:
    |
    | ->with('formularios')
    |
    | por lo que Laravel resuelve esto mediante eager loading.
    |
    */

    public function index(): JsonResponse
    {
        $modulos =
            $this->moduloService
                ->listar();

        $asignaciones =
            $modulos
                ->flatMap(
                    static function (
                        Modulo $modulo
                    ) {
                        return $modulo
                            ->formularios
                            ->map(
                                static fn (
                                    Formulario $formulario
                                ) => [
                                    'id_formulario' =>
                                        (int) $formulario->id,

                                    'id_modulo' =>
                                        (int) $modulo->id,

                                    'formulario' =>
                                        $formulario->formulario,

                                    'modulo' =>
                                        $modulo->modulo,
                                ]
                            );
                    }
                )
                ->values();

        return response()->json([
            'data' =>
                $asignaciones,

            'message' =>
                'Asignaciones formulario-módulo obtenidas correctamente.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Formularios de un módulo
    |--------------------------------------------------------------------------
    */

    public function show(
        Modulo $modulo
    ): JsonResponse {
        $modulo =
            $this->moduloService
                ->obtenerConFormularios(
                    $modulo
                );

        return $this
            ->responseModuloFormularios(
                $modulo,
                'Formularios del módulo obtenidos correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar formularios
    |--------------------------------------------------------------------------
    */

    public function sync(
        SyncFormulariosModuloRequest $request,
        Modulo $modulo
    ): JsonResponse {
        $modulo =
            $this->moduloService
                ->sincronizarFormularios(
                    $modulo,
                    $request->validated()[
                        'formulario_ids'
                    ]
                );

        return $this
            ->responseModuloFormularios(
                $modulo,
                'Formularios del módulo sincronizados correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Desasignar formulario
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Modulo $modulo,
        Formulario $formulario
    ): JsonResponse {
        $this->moduloService
            ->desasignarFormulario(
                $modulo,
                $formulario
            );

        return response()->json([
            'message' =>
                'Formulario desasignado del módulo correctamente.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Response individual módulo ↔ formularios
    |--------------------------------------------------------------------------
    */

    private function responseModuloFormularios(
        Modulo $modulo,
        string $message
    ): JsonResponse {
        return response()->json([
            'data' => [
                'modulo' => [
                    'id' =>
                        (int) $modulo->id,

                    'modulo' =>
                        $modulo->modulo,
                ],

                'formularios' =>
                    $modulo
                        ->formularios
                        ->map(
                            static fn (
                                Formulario $formulario
                            ) => [
                                'id' =>
                                    (int) $formulario->id,

                                'formulario' =>
                                    $formulario->formulario,

                                'ruta' =>
                                    $formulario->ruta,

                                'descripcion' =>
                                    $formulario->descripcion,

                                'estado' =>
                                    $formulario->estado,
                            ]
                        )
                        ->values(),
            ],

            'message' =>
                $message,
        ]);
    }
}