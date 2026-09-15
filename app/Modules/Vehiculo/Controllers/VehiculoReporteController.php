<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vehiculo\Requests\VehiculoReporteRequest;
use App\Modules\Vehiculo\Services\VehiculoReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class VehiculoReporteController extends Controller
{
    public function __construct(
        private readonly VehiculoReporteService $service
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | HTML PARA IMPRESIÓN
    |--------------------------------------------------------------------------
    */

    public function html(
        VehiculoReporteRequest $request,
        string $tipo
    ): Response {
        $filtros = $request->validated();

        $reporte = $this->service->obtener($tipo, $filtros);

        return response()
            ->view(
                $this->vista($tipo),
                $this->datosVista($reporte, $filtros)
            )
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function pdf(
        VehiculoReporteRequest $request,
        string $tipo
    ): Response {
        $filtros = $request->validated();

        $reporte = $this->service->obtener($tipo, $filtros);

        $pdf = Pdf::loadView(
            $this->vista($tipo),
            $this->datosVista($reporte, $filtros)
        )->setPaper('letter', 'landscape');

        return $pdf->download(
            $this->nombreArchivo($tipo, 'pdf')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function vista(string $tipo): string
    {
        $mapa = [
            'lista' => 'vehiculos/lista',
            'disponibles' => 'vehiculos/disponibles',
            'asignados' => 'vehiculos/asignados',
            'por_propietario' => 'vehiculos/por_propietario',
        ];

        if (!isset($mapa[$tipo])) {
            throw new \InvalidArgumentException(
                'El tipo de reporte de vehículos no es válido.'
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

    private function nombreArchivo(string $tipo, string $extension): string
    {
        return sprintf(
            'reporte-vehiculos-%s-%s.%s',
            str_replace('_', '-', $tipo),
            now()->format('Ymd-His'),
            $extension
        );
    }
}