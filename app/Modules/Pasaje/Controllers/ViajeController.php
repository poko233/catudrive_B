<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\CrearViajeRequest;
use App\Modules\Pasaje\Requests\ListarViajesRequest;
use App\Modules\Pasaje\Requests\UpdateEstadoViajeRequest;
use App\Modules\Pasaje\Resources\ViajeResource;
use App\Modules\Pasaje\Services\VentaService;
use App\Shared\Models\Viaje;
use App\Shared\Security\SecurityResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ViajeController
{
    public function __construct(
        private readonly VentaService $ventaService,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR VIAJES
    |--------------------------------------------------------------------------
    */

    public function index(
        ListarViajesRequest $request
    ): AnonymousResourceCollection {
        return ViajeResource::collection(
            $this->ventaService->listarViajes(
                $request->validated(),
                (int) $request->input(
                    'per_page',
                    15
                )
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRÓXIMA HORA DISPONIBLE DE UNA RUTA
    |--------------------------------------------------------------------------
    |
    | Ejemplo:
    |
    | Ruta inicia 06:00
    |
    | No hay viajes:
    | → 06:00
    |
    | Existe 06:00:
    | → 06:30
    |
    | Existe 06:30:
    | → 07:00
    |
    */

    public function proximaHora(
        int $ruta
    ): JsonResponse {
        try {
            return response()->json([
                'data' =>
                    $this->ventaService
                        ->obtenerProximaHoraRuta(
                            $ruta
                        ),
            ]);
        } catch (
            ModelNotFoundException
        ) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'Ruta no encontrada.',

                'code' =>
                    'NOT_FOUND',
            ], 404);
        } catch (
            RuntimeException $e
        ) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $e->getMessage(),

                'code' =>
                    'INVALID_ROUTE',
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR VIAJE
    |--------------------------------------------------------------------------
    */

    public function store(
        CrearViajeRequest $request
    ): JsonResponse|ViajeResource {
        try {
            $data =
                $request->validated();

            /*
            |--------------------------------------------------------------------------
            | COMPATIBILIDAD CON FLUJO ANTERIOR
            |--------------------------------------------------------------------------
            */

            if (
                !empty(
                    $data[
                        'id_vehiculo_chofer_ruta'
                    ]
                )
            ) {
                return new ViajeResource(
                    $this->ventaService
                        ->crearViaje(
                            (int)
                            $data[
                                'id_vehiculo_chofer_ruta'
                            ]
                        )
                );
            }

            /*
            |--------------------------------------------------------------------------
            | NUEVO FLUJO
            |--------------------------------------------------------------------------
            |
            | Frontend:
            |
            | Asignación
            | +
            | Ruta
            |
            | Backend:
            |
            | calcula hora
            | ↓
            | crea VCR
            | ↓
            | crea viaje
            |
            */

            return new ViajeResource(
                $this->ventaService
                    ->crearViajeProgramado(
                        (int)
                        $data[
                            'id_asignacion_vehiculo_chofer'
                        ],

                        (int)
                        $data[
                            'id_ruta'
                        ]
                    )
            );
        } catch (
            AccessDeniedHttpException $e
        ) {
            return SecurityResponse::forbidden(
                $e->getMessage()
            );
        } catch (
            ModelNotFoundException
        ) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'La asignación o ruta seleccionada no existe.',

                'code' =>
                    'NOT_FOUND',
            ], 404);
        } catch (
            RuntimeException $e
        ) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $e->getMessage(),

                'code' =>
                    'INVALID_TRIP',
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CAMBIAR ESTADO
    |--------------------------------------------------------------------------
    */

    public function updateEstado(
        UpdateEstadoViajeRequest $request,
        Viaje $viaje
    ): JsonResponse|ViajeResource {
        try {
            return new ViajeResource(
                $this->ventaService
                    ->actualizarEstadoViaje(
                        $viaje,
                        $request
                            ->validated()[
                                'estado'
                            ]
                    )
            );
        } catch (
            AccessDeniedHttpException $e
        ) {
            return SecurityResponse::forbidden(
                $e->getMessage()
            );
        }
    }
}