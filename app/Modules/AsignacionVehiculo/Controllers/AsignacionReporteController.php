<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AsignacionVehiculo\Requests\AsignacionReporteRequest;
use App\Modules\AsignacionVehiculo\Services\AsignacionReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class AsignacionReporteController extends Controller
{
    public function __construct(
        private readonly AsignacionReporteService $service
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | HTML PARA IMPRESIÓN
    |--------------------------------------------------------------------------
    */

    public function html(
        AsignacionReporteRequest $request,
        string $tipo
    ): Response {
        $filtros =
            $request->validated();

        $reporte =
            $this->service->obtener(
                $tipo,
                $filtros
            );

        return response()
            ->view(
                $this->vista(
                    $tipo
                ),
                $this->datosVista(
                    $reporte,
                    $filtros
                )
            )
            ->header(
                'Content-Type',
                'text/html; charset=UTF-8'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function pdf(
        AsignacionReporteRequest $request,
        string $tipo
    ): Response {
        $filtros =
            $request->validated();

        $reporte =
            $this->service->obtener(
                $tipo,
                $filtros
            );

        $pdf =
            Pdf::loadView(
                $this->vista(
                    $tipo
                ),
                $this->datosVista(
                    $reporte,
                    $filtros
                )
            )
                ->setPaper(
                    'letter',
                    'landscape'
                );

        return $pdf->download(
            $this->nombreArchivo(
                $tipo,
                'pdf'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function vista(
        string $tipo
    ): string {
        $mapa = [
            'por_chofer' =>
                'asignaciones/por_chofer',

            'historial' =>
                'asignaciones/historial',

            'sin_asignar' =>
                'asignaciones/sin_asignar',
        ];

        if (
            !isset(
                $mapa[$tipo]
            )
        ) {
            throw new \InvalidArgumentException(
                'El tipo de reporte de asignaciones no es válido.'
            );
        }

        return $mapa[$tipo];
    }

    private function datosVista(
        array $reporte,
        array $filtros
    ): array {
        return [
            'reporte' =>
                $reporte,

            'filtros' =>
                $filtros,

            'generadoEn' =>
                now(),
        ];
    }

    private function nombreArchivo(
        string $tipo,
        string $extension
    ): string {
        return sprintf(
            'reporte-asignaciones-%s-%s.%s',
            str_replace(
                '_',
                '-',
                $tipo
            ),
            now()->format(
                'Ymd-His'
            ),
            $extension
        );
    }
}
