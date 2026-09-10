<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Encomienda\Requests\EncomiendaReporteRequest;
use App\Modules\Encomienda\Resource\EncomiendaResource;
use App\Modules\Encomienda\Services\EncomiendaReporteService;
use App\Shared\Models\Encomienda;
use App\Shared\Models\Ruta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    | HTML PARA IMPRESIÓN
    |--------------------------------------------------------------------------
    */

    public function html(
        EncomiendaReporteRequest $request,
        string $tipo
    ): Response {
        $filtros =
            $request->validated();

        $reporte =
            $this->service
                ->obtener(
                    $tipo,
                    $filtros
                );

        return response()
            ->view(
                'encomiendas.reporte',
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
        EncomiendaReporteRequest $request,
        string $tipo
    ): Response {
        $filtros =
            $request->validated();

        $reporte =
            $this->service
                ->obtener(
                    $tipo,
                    $filtros
                );

        $pdf =
            Pdf::loadView(
                'encomiendas.reporte',
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
    | CSV
    |--------------------------------------------------------------------------
    */

    public function csv(
        EncomiendaReporteRequest $request,
        string $tipo
    ): StreamedResponse {
        $reporte =
            $this->service
                ->obtener(
                    $tipo,
                    $request->validated()
                );

        return response()->streamDownload(
            function () use (
                $reporte
            ): void {
                $output =
                    fopen(
                        'php://output',
                        'w'
                    );

                if ($output === false) {
                    return;
                }

                fwrite(
                    $output,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $output,
                    [
                        'Guía',
                        'Fecha',
                        'Origen',
                        'Destino',
                        'Remitente',
                        'Destinatario',
                        'Descripción',
                        'Cantidad',
                        'Precio (Bs)',
                        'Estado',
                        'Viaje',
                        'Hora salida',
                        'Chofer',
                        'Vehículo',
                    ],
                    ';'
                );

                foreach (
                    $reporte[
                        'items'
                    ] as $item
                ) {
                    if (!$item instanceof Encomienda) {
                        continue;
                    }

                    $viaje =
                        $item
                            ->viajeEncomienda
                            ?->viaje;

                    $vehiculoChoferRuta =
                        $viaje
                            ?->vehiculoChoferRuta;

                    $asignacion =
                        $vehiculoChoferRuta
                            ?->asignacion;

                    $usuario =
                        $asignacion
                            ?->chofer
                            ?->usuario;

                    $vehiculo =
                        $asignacion
                            ?->vehiculo;

                    $nombreChofer =
                        trim(
                            implode(
                                ' ',
                                array_filter([
                                    $usuario?->nombres,
                                    $usuario?->primer_apellido,
                                    $usuario?->segundo_apellido,
                                ])
                            )
                        );

                    $nombreVehiculo =
                        trim(
                            implode(
                                ' ',
                                array_filter([
                                    $vehiculo?->placa,
                                    $vehiculo?->marca,
                                    $vehiculo?->modelo,
                                ])
                            )
                        );

                    fputcsv(
                        $output,
                        [
                            $item->guia,
                            $item->created_at?->format('d/m/Y'),
                            $item->ruta?->origen,
                            $item->ruta?->destino,
                            $item->remitente,
                            $item->destinatario,
                            $item->descripcion,
                            $item->cantidad,
                            number_format(
                                (float)
                                $item->precio,
                                2,
                                '.',
                                ''
                            ),
                            $item->estado,
                            $viaje?->id,
                            $vehiculoChoferRuta
                                ?->hora_inicio
                                ?->format('d/m/Y H:i'),
                            $nombreChofer,
                            $nombreVehiculo,
                        ],
                        ';'
                    );
                }

                if (
                    $reporte[
                        'tipo'
                    ] === 'por_destino'
                ) {
                    fputcsv(
                        $output,
                        [],
                        ';'
                    );

                    fputcsv(
                        $output,
                        [
                            'RESUMEN POR DESTINO',
                        ],
                        ';'
                    );

                    fputcsv(
                        $output,
                        [
                            'Destino',
                            'Cantidad',
                            'Total (Bs)',
                        ],
                        ';'
                    );

                    foreach (
                        $reporte[
                            'resumen_destinos'
                        ] ?? [] as $resumen
                    ) {
                        fputcsv(
                            $output,
                            [
                                $resumen[
                                    'destino'
                                ],
                                $resumen[
                                    'cantidad'
                                ],
                                $resumen[
                                    'total'
                                ],
                            ],
                            ';'
                        );
                    }
                }

                if (
                    $reporte[
                        'tipo'
                    ] === 'ingresos'
                ) {
                    fputcsv(
                        $output,
                        [],
                        ';'
                    );

                    fputcsv(
                        $output,
                        [
                            'TOTAL INGRESOS',
                            $reporte[
                                'total_ingresos'
                            ] ?? '0.00',
                        ],
                        ';'
                    );
                }

                fclose(
                    $output
                );
            },
            $this->nombreArchivo(
                $tipo,
                'csv'
            ),
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DATOS DE VISTA
    |--------------------------------------------------------------------------
    */

    private function datosVista(
        array $reporte,
        array $filtros
    ): array {
        $ruta =
            !empty(
                $filtros[
                    'id_ruta'
                ]
            )
                ? Ruta::query()
                    ->find(
                        (int)
                        $filtros[
                            'id_ruta'
                        ]
                    )
                : null;

        return [
            'reporte' =>
                $reporte,

            'filtros' =>
                $filtros,

            'rutaFiltro' =>
                $ruta,

            'generadoEn' =>
                now(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | NOMBRE DE ARCHIVO
    |--------------------------------------------------------------------------
    */

    private function nombreArchivo(
        string $tipo,
        string $extension
    ): string {
        return sprintf(
            'reporte-encomiendas-%s-%s.%s',
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

    /*
    |--------------------------------------------------------------------------
    | RESOLVER JSON
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
