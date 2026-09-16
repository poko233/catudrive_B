<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chofer\Requests\ChoferReporteRequest;
use App\Modules\Chofer\Services\ChoferReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ChoferReporteController extends Controller
{
    public function __construct(
        private readonly ChoferReporteService $service
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | HTML PARA IMPRESIÓN
    |--------------------------------------------------------------------------
    */

    public function html(
        ChoferReporteRequest $request,
        string $tipo
    ): Response {
        $filtros = $request->validated();

        $reporte = $this->service->obtener(
            $tipo,
            $filtros
        );

        return response()
            ->view(
                $this->vista($tipo),
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
        ChoferReporteRequest $request,
        string $tipo
    ): Response {
        $filtros = $request->validated();

        $reporte = $this->service->obtener(
            $tipo,
            $filtros
        );

        $pdf = Pdf::loadView(
            $this->vista($tipo),
            $this->datosVista(
                $reporte,
                $filtros
            )
        )->setPaper(
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
            'lista' =>
                'choferes/lista',

            'activos_inactivos' =>
                'choferes/activos_inactivos',

            'carnets_sindicales' =>
                'choferes/carnets_sindicales',
        ];

        if (
            !isset(
                $mapa[$tipo]
            )
        ) {
            throw new \InvalidArgumentException(
                'El tipo de reporte de choferes no es válido.'
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
            'reporte-choferes-%s-%s.%s',
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
