<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\CambiarAsientoRequest;
use App\Modules\Pasaje\Requests\ConfirmarVentaRequest;
use App\Modules\Pasaje\Requests\IniciarVentaRequest;
use App\Modules\Pasaje\Resources\VentaResource;
use App\Modules\Pasaje\Services\VentaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
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
        try {
            $asientos = $this->ventaService->obtenerAsientos($idViaje);

            return response()->json([
                'success' => true,
                'data' => $asientos,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Viaje no encontrado.',
                'code' => 'NOT_FOUND',
            ], 404);
        }
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
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Viaje no encontrado.',
                'code' => 'NOT_FOUND',
            ], 404);
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
                $request->validated()['pasajeros'] ?? []
            );

            return new VentaResource($venta);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada.',
                'code' => 'NOT_FOUND',
            ], 404);
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
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada.',
                'code' => 'NOT_FOUND',
            ], 404);
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
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada.',
                'code' => 'NOT_FOUND',
            ], 404);
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
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle no encontrado.',
                'code' => 'NOT_FOUND',
            ], 404);
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
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle no encontrado.',
                'code' => 'NOT_FOUND',
            ], 404);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'BUSINESS_ERROR',
            ], 409);
        }
    }

    public function show(int $ventaId): JsonResponse|VentaResource
    {
        try {
            $venta = $this->ventaService->obtenerVenta($ventaId);
            return new VentaResource($venta);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada.',
                'code' => 'NOT_FOUND',
            ], 404);
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
        try {
            $pdf = $this->ventaService->generarPdfVenta($ventaId);
            return $pdf->download("boleto-{$ventaId}.pdf");
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada.',
                'code' => 'NOT_FOUND',
            ], 404);
        }
    }
    public function ticketHtml(int $ventaId): Response
    {
        try {
            $venta = $this->ventaService->obtenerVenta($ventaId);
            $qrData = $this->ventaService->generarQrData($ventaId);

            return response()->view('pasajes.ticket-thermal', [
                'venta' => $venta,
                'qrData' => $qrData,
            ])->header('Content-Type', 'text/html');
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada.',
                'code' => 'NOT_FOUND',
            ], 404);
        }
    }
}