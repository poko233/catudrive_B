<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pasaje\Requests\PasajeReporteRequest;
use App\Modules\Pasaje\Services\PasajeReporteService;
use App\Shared\Security\SecurityResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PasajeReporteController extends Controller
{
    public function __construct(
        private readonly PasajeReporteService $service,
    ) {
    }

    // ─────────────────────────────────────────────────────────────
    // ANALÍTICOS (HTML / PDF)
    // ─────────────────────────────────────────────────────────────

    public function html(PasajeReporteRequest $request, string $tipo): Response
    {
        try {
            $filtros = $request->validated();
            $reporte = $this->service->obtener($tipo, $filtros);

            return response()
                ->view($this->vista($tipo), $this->datosVista($reporte, $filtros))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        }
    }

    public function pdf(PasajeReporteRequest $request, string $tipo): Response
    {
        try {
            $filtros = $request->validated();
            $reporte = $this->service->obtener($tipo, $filtros);

            $pdf = Pdf::loadView($this->vista($tipo), $this->datosVista($reporte, $filtros))
                ->setPaper('letter', 'landscape');

            return $pdf->download($this->nombreArchivo($tipo));
        } catch (AccessDeniedHttpException $e) {
            return SecurityResponse::forbidden($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // PLANILLA DE PASAJEROS (por viaje)
    // ─────────────────────────────────────────────────────────────

    public function planillaHtml(int $idViaje): Response
    {
        try {
            $reporte = $this->service->planillaPasajeros($idViaje);

            return response()
                ->view('pasajes/planilla_pasajeros', [
                    'reporte' => $reporte,
                    'generadoEn' => now(),
                ])
                ->header('Content-Type', 'text/html; charset=UTF-8');
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

    public function planillaPdf(int $idViaje): Response
    {
        try {
            $reporte = $this->service->planillaPasajeros($idViaje);

            $pdf = Pdf::loadView('pasajes/planilla_pasajeros', [
                'reporte' => $reporte,
                'generadoEn' => now(),
            ])->setPaper('letter', 'portrait');

            return $pdf->download(
                'planilla-pasajeros-viaje-' . $idViaje . '-' . now()->format('Ymd-His') . '.pdf'
            );
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

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function vista(string $tipo): string
    {
        $mapa = [
            'vendidos_por_fecha' => 'pasajes/vendidos_por_fecha',
            'por_ruta' => 'pasajes/por_ruta',
            'por_vehiculo' => 'pasajes/por_vehiculo',
            'por_chofer' => 'pasajes/por_chofer',
            'ingresos' => 'pasajes/ingresos',
        ];

        if (!isset($mapa[$tipo])) {
            throw new \InvalidArgumentException(
                'El tipo de reporte de pasajes no es válido.'
            );
        }

        return $mapa[$tipo];
    }

    private function datosVista(array $reporte, array $filtros): array
    {
        return [
            'reporte' => $reporte,
            'filtros' => $filtros,
            'generadoEn' => now(),
        ];
    }

    private function nombreArchivo(string $tipo): string
    {
        return sprintf(
            'reporte-pasajes-%s-%s.pdf',
            str_replace('_', '-', $tipo),
            now()->format('Ymd-His')
        );
    }
}