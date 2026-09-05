<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\CambiarAsientoRequest;
use App\Modules\Pasaje\Requests\ConfirmarVentaRequest;
use App\Modules\Pasaje\Requests\IniciarVentaRequest;
use App\Modules\Pasaje\Resources\VentaResource;
use App\Modules\Pasaje\Services\VentaService;
use App\Shared\Models\DetalleVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class VentaController
{
    public function __construct(
        private readonly VentaService $ventaService,
    ) {
    }

    public function obtenerAsientos(int $idViaje): JsonResponse
    {
        $asientos = $this->ventaService->obtenerAsientos($idViaje);

        return response()->json([
            'success' => true,
            'data' => $asientos,
        ]);
    }

    public function iniciarVenta(IniciarVentaRequest $request): JsonResponse|VentaResource
    {
        try {
            $venta = $this->ventaService->iniciarVenta(
                (int) $request->validated()['id_viaje'],
                $request->validated()['asientos'],
                (int) $request->user()->id
            );

            return new VentaResource($venta);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 409);
        }
    }

    public function confirmarVenta(int $ventaId, ConfirmarVentaRequest $request): JsonResponse|VentaResource
    {
        try {
            $venta = $this->ventaService->confirmarVenta(
                $ventaId,
                $request->validated()['forma_pago'],
                $request->validated()['pasajeros']
            );

            return new VentaResource($venta);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 409);
        }
    }

    public function cancelarVenta(int $ventaId): JsonResponse
    {
        try {
            $this->ventaService->cancelarVenta($ventaId);

            return response()->json([
                'success' => true,
                'message' => 'Venta cancelada correctamente.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 409);
        }
    }

    public function anularVenta(int $ventaId): JsonResponse
    {
        try {
            $this->ventaService->anularVenta($ventaId);

            return response()->json([
                'success' => true,
                'message' => 'Venta anulada correctamente.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 409);
        }
    }

    public function eliminarDetalle(int $detalleId): JsonResponse
    {
        try {
            $this->ventaService->eliminarDetalle($detalleId);

            return response()->json([
                'success' => true,
                'message' => 'Detalle eliminado, asiento liberado.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 409);
        }
    }

    public function cambiarAsiento(int $detalleId, CambiarAsientoRequest $request): JsonResponse
    {
        try {
            $detalle = $this->ventaService->cambiarAsiento(
                $detalleId,
                (int) $request->validated()['nuevo_id_asiento']
            );

            return response()->json([
                'success' => true,
                'data' => new VentaResource(
                    $detalle->venta->load(
                        'detalles.pasajero',
                        'detalles.asiento',
                        'viaje.vehiculoChoferRuta.ruta'
                    )
                ),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 409);
        }
    }

    public function generarPdf(int $ventaId): Response
    {
        $pdf = $this->ventaService->generarPdfVenta($ventaId);
        return $pdf->download("boleto-{$ventaId}.pdf");
    }
    public function asignarPasajero(int $detalleId, AsignarPasajeroRequest $request): JsonResponse
    {
        try {
            $detalle = $this->ventaService->asignarPasajero(
                $detalleId,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $detalle->id,
                    'asiento' => [
                        'id' => $detalle->asiento?->id,
                        'fila' => $detalle->asiento?->fila,
                        'columna' => $detalle->asiento?->columna,
                        'numero_asiento' => $detalle->asiento?->numero_asiento,
                    ],
                    'pasajero' => [
                        'id' => $detalle->pasajero?->id,
                        'nombres' => $detalle->pasajero?->nombres,
                        'apellido_paterno' => $detalle->pasajero?->apellido_paterno,
                        'apellido_materno' => $detalle->pasajero?->apellido_materno,
                        'ci' => $detalle->pasajero?->ci,
                    ],
                    'precio_unitario' => $detalle->precio_unitario,
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 409);
        }
    }
}