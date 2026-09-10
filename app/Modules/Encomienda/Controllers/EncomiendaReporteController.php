<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Encomienda\Requests\EncomiendaReporteRequest;
use App\Modules\Encomienda\Resource\EncomiendaResource;
use App\Modules\Encomienda\Services\EncomiendaReporteService;
use Illuminate\Http\JsonResponse;

class EncomiendaReporteController extends Controller
{
    public function __construct(
        private readonly EncomiendaReporteService $service
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTRADAS
    |--------------------------------------------------------------------------
    */

    public function registradas(
        EncomiendaReporteRequest $request
    ): JsonResponse {
        return response()->json(
            $this->resolver(
                $request,
                $this->service
                    ->registradas(
                        $request->validated()
                    )
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PENDIENTES
    |--------------------------------------------------------------------------
    */

    public function pendientes(
        EncomiendaReporteRequest $request
    ): JsonResponse {
        return response()->json(
            $this->resolver(
                $request,
                $this->service
                    ->pendientes(
                        $request->validated()
                    )
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ENTREGADAS
    |--------------------------------------------------------------------------
    */

    public function entregadas(
        EncomiendaReporteRequest $request
    ): JsonResponse {
        return response()->json(
            $this->resolver(
                $request,
                $this->service
                    ->entregadas(
                        $request->validated()
                    )
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | POR DESTINO
    |--------------------------------------------------------------------------
    */

    public function porDestino(
        EncomiendaReporteRequest $request
    ): JsonResponse {
        return response()->json(
            $this->resolver(
                $request,
                $this->service
                    ->porDestino(
                        $request->validated()
                    )
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INGRESOS
    |--------------------------------------------------------------------------
    */

    public function ingresos(
        EncomiendaReporteRequest $request
    ): JsonResponse {
        return response()->json(
            $this->resolver(
                $request,
                $this->service
                    ->ingresos(
                        $request->validated()
                    )
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVER
    |--------------------------------------------------------------------------
    */

    private function resolver(
        EncomiendaReporteRequest $request,
        array $reporte
    ): array {
        $reporte[
            'items'
        ] =
            collect(
                $reporte[
                    'items'
                ] ?? []
            )
                ->map(
                    fn ($item) =>
                        (
                            new EncomiendaResource(
                                $item
                            )
                        )->resolve(
                            $request
                        )
                )
                ->values();

        return $reporte;
    }
}