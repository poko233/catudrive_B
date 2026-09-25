<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\CambiarAsientoRequest;
use App\Modules\Pasaje\Requests\ConfirmarVentaRequest;
use App\Modules\Pasaje\Requests\IniciarVentaRequest;
use App\Modules\Pasaje\Resources\VentaResource;
use App\Modules\Pasaje\Services\PasajeroService;
use App\Modules\Pasaje\Services\VentaService;
use App\Shared\Security\SecurityResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VentaController
{
    public function __construct(
        private readonly VentaService $ventaService,
        private readonly PasajeroService $pasajeroService,
    ) {
    }

    public function obtenerAsientos(int $idViaje): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->ventaService->obtenerAsientos($idViaje),
            ]);
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
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
            return new VentaResource($this->ventaService->iniciarVenta(
                (int) $request->validated()['id_viaje'],
                $request->validated()['asientos'],
                (int) $request->user()->id
            ));
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Viaje no encontrado.', 'code' => 'NOT_FOUND'], 404);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'BUSINESS_ERROR'], 409);
        }
    }

    public function confirmarVenta(int $ventaId, ConfirmarVentaRequest $request): JsonResponse|VentaResource
    {
        try {
            $validated = $request->validated();

            $this->pasajeroService->vincularSeleccionados(
                $ventaId,
                $validated['pasajeros'] ?? []
            );

            return new VentaResource($this->ventaService->confirmarVenta(
                $ventaId,
                $validated['forma_pago'],
                $validated['pasajeros'] ?? []
            ));
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Venta no encontrada.', 'code' => 'NOT_FOUND'], 404);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'BUSINESS_ERROR'], 409);
        }
    }

    public function cancelarVenta(int $ventaId): JsonResponse
    {
        try {
            $this->ventaService->cancelarVenta($ventaId);
            return response()->json(['success' => true, 'message' => 'Venta cancelada correctamente.']);
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Venta no encontrada.', 'code' => 'NOT_FOUND'], 404);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'BUSINESS_ERROR'], 409);
        }
    }

    public function anularVenta(int $ventaId): JsonResponse
    {
        try {
            $this->ventaService->anularVenta($ventaId);
            return response()->json(['success' => true, 'message' => 'Venta anulada correctamente.']);
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Venta no encontrada.', 'code' => 'NOT_FOUND'], 404);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'BUSINESS_ERROR'], 409);
        }
    }

    public function eliminarDetalle(int $detalleId): JsonResponse
    {
        try {
            $this->ventaService->eliminarDetalle($detalleId);
            return response()->json(['success' => true, 'message' => 'Detalle eliminado, asiento liberado.']);
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Detalle no encontrado.', 'code' => 'NOT_FOUND'], 404);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'BUSINESS_ERROR'], 409);
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
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Detalle no encontrado.', 'code' => 'NOT_FOUND'], 404);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'BUSINESS_ERROR'], 409);
        }
    }

    public function show(int $ventaId): JsonResponse|VentaResource
    {
        try {
            return new VentaResource($this->ventaService->obtenerVenta($ventaId));
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Venta no encontrada.', 'code' => 'NOT_FOUND'], 404);
        }
    }

    public function generarPdf(int $ventaId): Response
    {
        try {
            return $this->ventaService->generarPdfVenta($ventaId)->download("boleto-{$ventaId}.pdf");
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Venta no encontrada.', 'code' => 'NOT_FOUND'], 404);
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
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Venta no encontrada.', 'code' => 'NOT_FOUND'], 404);
        }
    }
}
