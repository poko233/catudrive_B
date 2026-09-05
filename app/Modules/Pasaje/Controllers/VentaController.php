<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Modules\Pasaje\Requests\CambiarAsientoRequest;
use App\Modules\Pasaje\Requests\ConfirmarVentaRequest;
use App\Modules\Pasaje\Requests\IniciarVentaRequest;
use App\Modules\Pasaje\Resources\AsientoOcupacionResource;
use App\Modules\Pasaje\Resources\VentaResource;
use App\Modules\Pasaje\Services\VentaService;
use App\Shared\Models\DetalleVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function iniciarVenta(IniciarVentaRequest $request): VentaResource
    {
        $venta = $this->ventaService->iniciarVenta(
            (int) $request->validated()['id_viaje'],
            $request->validated()['asientos'],
            (int) $request->user()->id
        );

        return new VentaResource($venta);
    }

    public function confirmarVenta(int $ventaId, ConfirmarVentaRequest $request): VentaResource
    {
        $venta = $this->ventaService->confirmarVenta(
            $ventaId,
            $request->validated()['forma_pago']
        );

        return new VentaResource($venta);
    }

    public function cancelarVenta(int $ventaId): JsonResponse
    {
        $this->ventaService->cancelarVenta($ventaId);

        return response()->json([
            'success' => true,
            'message' => 'Venta cancelada correctamente.',
        ]);
    }

    public function anularVenta(int $ventaId): JsonResponse
    {
        $this->ventaService->anularVenta($ventaId);

        return response()->json([
            'success' => true,
            'message' => 'Venta anulada correctamente.',
        ]);
    }

    public function eliminarDetalle(int $detalleId): JsonResponse
    {
        $this->ventaService->eliminarDetalle($detalleId);

        return response()->json([
            'success' => true,
            'message' => 'Detalle eliminado, asiento liberado.',
        ]);
    }

    public function cambiarAsiento(int $detalleId, CambiarAsientoRequest $request): JsonResponse
    {
        $detalle = $this->ventaService->cambiarAsiento(
            $detalleId,
            (int) $request->validated()['nuevo_id_asiento']
        );

        return response()->json([
            'success' => true,
            'data' => new VentaResource($detalle->venta->load('detalles.pasajero', 'detalles.asiento')),
        ]);
    }

    public function generarPdf(int $ventaId): Response
    {
        $pdf = $this->ventaService->generarPdfVenta($ventaId);
        return $pdf->download("boleto-{$ventaId}.pdf");
    }
}