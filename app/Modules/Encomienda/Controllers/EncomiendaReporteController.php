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
use ZipArchive;

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
    | EXCEL
    |--------------------------------------------------------------------------
    |
    | La ruta se mantiene compatible con el endpoint CSV existente para no
    | romper el frontend ni las rutas actuales, pero la descarga se genera como
    | un archivo XLSX real. CSV no permite estilos, anchos de columnas, bordes,
    | títulos ni formatos profesionales.
    |
    */

    public function csv(
        EncomiendaReporteRequest $request,
        string $tipo
    ): StreamedResponse {
        $filtros =
            $request->validated();

        $reporte =
            $this->service
                ->obtener(
                    $tipo,
                    $filtros
                );

        $archivo =
            $this->crearExcel(
                $reporte,
                $filtros
            );

        return response()->streamDownload(
            function () use (
                $archivo
            ): void {
                $stream =
                    fopen(
                        $archivo,
                        'rb'
                    );

                if ($stream !== false) {
                    fpassthru(
                        $stream
                    );

                    fclose(
                        $stream
                    );
                }

                @unlink(
                    $archivo
                );
            },
            $this->nombreArchivo(
                $tipo,
                'xlsx'
            ),
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR EXCEL XLSX
    |--------------------------------------------------------------------------
    */

    private function crearExcel(
        array $reporte,
        array $filtros
    ): string {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException(
                'La extensión ZIP de PHP es necesaria para generar el archivo Excel.'
            );
        }

        $rutaFiltro =
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

        $filas =
            [];

        foreach (
            $reporte[
                'items'
            ] ?? [] as $item
        ) {
            if (!$item instanceof Encomienda) {
                continue;
            }

            $filas[] =
                $this->filaExcel(
                    $item
                );
        }

        $titulo =
            (string) (
                $reporte[
                    'titulo'
                ] ??
                'Reporte de Encomiendas'
            );

        $detalleFiltros =
            $this->descripcionFiltrosExcel(
                $filtros,
                $rutaFiltro
            );

        $columnas =
            [
                [
                    'titulo' => 'Guía',
                    'ancho' => 16,
                ],
                [
                    'titulo' => 'Fecha',
                    'ancho' => 13,
                ],
                [
                    'titulo' => 'Origen',
                    'ancho' => 18,
                ],
                [
                    'titulo' => 'Destino',
                    'ancho' => 18,
                ],
                [
                    'titulo' => 'Remitente',
                    'ancho' => 24,
                ],
                [
                    'titulo' => 'Destinatario',
                    'ancho' => 24,
                ],
                [
                    'titulo' => 'Descripción',
                    'ancho' => 38,
                ],
                [
                    'titulo' => 'Cantidad',
                    'ancho' => 11,
                ],
                [
                    'titulo' => 'Precio (Bs)',
                    'ancho' => 14,
                ],
                [
                    'titulo' => 'Estado',
                    'ancho' => 15,
                ],
                [
                    'titulo' => 'Viaje',
                    'ancho' => 10,
                ],
                [
                    'titulo' => 'Hora salida',
                    'ancho' => 20,
                ],
                [
                    'titulo' => 'Chofer',
                    'ancho' => 28,
                ],
                [
                    'titulo' => 'Vehículo',
                    'ancho' => 30,
                ],
            ];

        $sheet =
            $this->crearSheetXml(
                $titulo,
                $detalleFiltros,
                $columnas,
                $filas,
                $reporte
            );

        $archivo =
            tempnam(
                sys_get_temp_dir(),
                'encomiendas_excel_'
            );

        if ($archivo === false) {
            throw new \RuntimeException(
                'No se pudo crear el archivo temporal para el reporte Excel.'
            );
        }

        $xlsx =
            $archivo .
            '.xlsx';

        @rename(
            $archivo,
            $xlsx
        );

        $zip =
            new ZipArchive();

        if (
            $zip->open(
                $xlsx,
                ZipArchive::CREATE |
                ZipArchive::OVERWRITE
            ) !== true
        ) {
            throw new \RuntimeException(
                'No se pudo construir el archivo Excel.'
            );
        }

        $zip->addFromString(
            '[Content_Types].xml',
            $this->excelContentTypes()
        );

        $zip->addFromString(
            '_rels/.rels',
            $this->excelRootRels()
        );

        $zip->addFromString(
            'docProps/app.xml',
            $this->excelAppProps()
        );

        $zip->addFromString(
            'docProps/core.xml',
            $this->excelCoreProps()
        );

        $zip->addFromString(
            'xl/workbook.xml',
            $this->excelWorkbook()
        );

        $zip->addFromString(
            'xl/_rels/workbook.xml.rels',
            $this->excelWorkbookRels()
        );

        $zip->addFromString(
            'xl/styles.xml',
            $this->excelStyles()
        );

        $zip->addFromString(
            'xl/worksheets/sheet1.xml',
            $sheet
        );

        $zip->close();

        return $xlsx;
    }

    /*
    |--------------------------------------------------------------------------
    | FILA EXCEL
    |--------------------------------------------------------------------------
    */

    private function filaExcel(
        Encomienda $item
    ): array {
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

        return [
            (string) (
                $item->guia ??
                ''
            ),
            $item->created_at
                ?->format(
                    'd/m/Y'
                ) ??
                '',
            (string) (
                $item->ruta?->origen ??
                ''
            ),
            (string) (
                $item->ruta?->destino ??
                ''
            ),
            (string) (
                $item->remitente ??
                ''
            ),
            (string) (
                $item->destinatario ??
                ''
            ),
            (string) (
                $item->descripcion ??
                ''
            ),
            (int) (
                $item->cantidad ??
                0
            ),
            (float) (
                $item->precio ??
                0
            ),
            (string) (
                $item->estado ??
                ''
            ),
            $viaje?->id !== null
                ? (int)
                    $viaje->id
                : '',
            $vehiculoChoferRuta
                ?->hora_inicio
                ?->format(
                    'd/m/Y H:i'
                ) ??
                '',
            $nombreChofer,
            $nombreVehiculo,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DESCRIPCIÓN DE FILTROS EXCEL
    |--------------------------------------------------------------------------
    */

    private function descripcionFiltrosExcel(
        array $filtros,
        ?Ruta $ruta
    ): string {
        $desde =
            !empty(
                $filtros[
                    'fecha_inicio'
                ]
            )
                ? date(
                    'd/m/Y',
                    strtotime(
                        (string)
                        $filtros[
                            'fecha_inicio'
                        ]
                    )
                )
                : 'Sin límite';

        $hasta =
            !empty(
                $filtros[
                    'fecha_fin'
                ]
            )
                ? date(
                    'd/m/Y',
                    strtotime(
                        (string)
                        $filtros[
                            'fecha_fin'
                        ]
                    )
                )
                : 'Sin límite';

        $rutaTexto =
            $ruta
                ? sprintf(
                    '%s → %s',
                    $ruta->origen,
                    $ruta->destino
                )
                : 'Todas las rutas';

        return sprintf(
            'Periodo: %s - %s  |  Ruta: %s  |  Generado: %s',
            $desde,
            $hasta,
            $rutaTexto,
            now()->format(
                'd/m/Y H:i'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | XML HOJA EXCEL
    |--------------------------------------------------------------------------
    */

    private function crearSheetXml(
        string $titulo,
        string $detalleFiltros,
        array $columnas,
        array $filas,
        array $reporte
    ): string {
        $ultimaColumna =
            $this->letraColumna(
                count(
                    $columnas
                )
            );

        $mergeResumen =
            null;

        $xml =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetViews><sheetView workbookViewId="0">' .
            '<pane ySplit="6" topLeftCell="A7" activePane="bottomLeft" state="frozen"/>' .
            '</sheetView></sheetViews>' .
            '<sheetFormatPr defaultRowHeight="18"/>' .
            '<cols>';

        foreach (
            $columnas as $indice => $columna
        ) {
            $numero =
                $indice +
                1;

            $xml .=
                sprintf(
                    '<col min="%d" max="%d" width="%s" customWidth="1"/>',
                    $numero,
                    $numero,
                    $this->numeroXml(
                        (float)
                        $columna[
                            'ancho'
                        ]
                    )
                );
        }

        $xml .=
            '</cols><sheetData>';

        $xml .=
            $this->filaXml(
                1,
                [
                    [
                        'valor' =>
                            strtoupper(
                                $titulo
                            ),
                        'estilo' => 1,
                    ],
                ],
                30
            );

        $xml .=
            $this->filaXml(
                2,
                [
                    [
                        'valor' =>
                            'CATUDRIVE · REPORTE DE ENCOMIENDAS',
                        'estilo' => 2,
                    ],
                ],
                22
            );

        $xml .=
            $this->filaXml(
                3,
                [
                    [
                        'valor' =>
                            $detalleFiltros,
                        'estilo' => 3,
                    ],
                ],
                22
            );

        $xml .=
            $this->filaXml(
                4,
                [
                    [
                        'valor' =>
                            sprintf(
                                'Registros: %d',
                                count(
                                    $filas
                                )
                            ),
                        'estilo' => 3,
                    ],
                ],
                22
            );

        $xml .=
            $this->filaXml(
                5,
                [],
                8
            );

        $encabezados =
            [];

        foreach (
            $columnas as $columna
        ) {
            $encabezados[] =
                [
                    'valor' =>
                        $columna[
                            'titulo'
                        ],
                    'estilo' => 4,
                ];
        }

        $xml .=
            $this->filaXml(
                6,
                $encabezados,
                28
            );

        $filaActual =
            7;

        foreach (
            $filas as $fila
        ) {
            $celdas =
                [];

            foreach (
                $fila as $indice => $valor
            ) {
                $estilo =
                    5;

                if ($indice === 7) {
                    $estilo =
                        6;
                }

                if ($indice === 8) {
                    $estilo =
                        7;
                }

                if ($indice === 9) {
                    $estilo =
                        $this->estiloEstadoExcel(
                            (string)
                            $valor
                        );
                }

                $celdas[] =
                    [
                        'valor' =>
                            $valor,
                        'estilo' =>
                            $estilo,
                        'numero' =>
                            is_int(
                                $valor
                            ) ||
                            is_float(
                                $valor
                            ),
                    ];
            }

            $xml .=
                $this->filaXml(
                    $filaActual,
                    $celdas,
                    22
                );

            $filaActual++;
        }

        $ultimaFilaDatos =
            max(
                6,
                $filaActual -
                1
            );

        if (
            ($reporte[
                'tipo'
            ] ?? null) ===
            'por_destino'
        ) {
            $filaActual +=
                1;

            $xml .=
                $this->filaXml(
                    $filaActual,
                    [
                        [
                            'valor' =>
                                'RESUMEN POR DESTINO',
                            'estilo' => 8,
                        ],
                    ],
                    26
                );

            $filaResumenTitulo =
                $filaActual;

            $filaActual++;

            $xml .=
                $this->filaXml(
                    $filaActual,
                    [
                        [
                            'valor' => 'Destino',
                            'estilo' => 4,
                        ],
                        [
                            'valor' => 'Cantidad',
                            'estilo' => 4,
                        ],
                        [
                            'valor' => 'Total (Bs)',
                            'estilo' => 4,
                        ],
                    ],
                    25
                );

            foreach (
                $reporte[
                    'resumen_destinos'
                ] ?? [] as $resumen
            ) {
                $filaActual++;

                $xml .=
                    $this->filaXml(
                        $filaActual,
                        [
                            [
                                'valor' =>
                                    $resumen[
                                        'destino'
                                    ] ?? '',
                                'estilo' => 5,
                            ],
                            [
                                'valor' =>
                                    (int) (
                                        $resumen[
                                            'cantidad'
                                        ] ?? 0
                                    ),
                                'estilo' => 6,
                                'numero' => true,
                            ],
                            [
                                'valor' =>
                                    (float) (
                                        $resumen[
                                            'total'
                                        ] ?? 0
                                    ),
                                'estilo' => 7,
                                'numero' => true,
                            ],
                        ],
                        22
                    );
            }

            $mergeResumen =
                sprintf(
                    '<mergeCell ref="A%d:C%d"/>',
                    $filaResumenTitulo,
                    $filaResumenTitulo
                );
        }

        if (
            ($reporte[
                'tipo'
            ] ?? null) ===
            'ingresos'
        ) {
            $filaActual +=
                2;

            $xml .=
                $this->filaXml(
                    $filaActual,
                    [
                        [
                            'valor' =>
                                'TOTAL INGRESOS',
                            'estilo' => 8,
                        ],
                        [
                            'valor' =>
                                (float) (
                                    $reporte[
                                        'total_ingresos'
                                    ] ?? 0
                                ),
                            'estilo' => 9,
                            'numero' => true,
                        ],
                    ],
                    28
                );
        }

        $merges =
            sprintf(
                '<mergeCell ref="A1:%1$s1"/><mergeCell ref="A2:%1$s2"/><mergeCell ref="A3:%1$s3"/><mergeCell ref="A4:%1$s4"/>',
                $ultimaColumna
            );

        $cantidadMerges =
            4;

        if ($mergeResumen !== null) {
            $merges .=
                $mergeResumen;

            $cantidadMerges++;
        }

        $xml .=
            '</sheetData>' .
            sprintf(
                '<autoFilter ref="A6:%s%d"/>',
                $ultimaColumna,
                $ultimaFilaDatos
            ) .
            sprintf(
                '<mergeCells count="%d">%s</mergeCells>',
                $cantidadMerges,
                $merges
            ) .
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' .
            '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>' .
            '</worksheet>';

        return $xml;
    }

    private function filaXml(
        int $numeroFila,
        array $celdas,
        int $alto
    ): string {
        $xml =
            sprintf(
                '<row r="%d" ht="%d" customHeight="1">',
                $numeroFila,
                $alto
            );

        foreach (
            $celdas as $indice => $celda
        ) {
            $referencia =
                $this->letraColumna(
                    $indice +
                    1
                ) .
                $numeroFila;

            $estilo =
                (int) (
                    $celda[
                        'estilo'
                    ] ?? 0
                );

            $valor =
                $celda[
                    'valor'
                ] ?? '';

            if (
                ($celda[
                    'numero'
                ] ?? false) &&
                is_numeric(
                    $valor
                )
            ) {
                $xml .=
                    sprintf(
                        '<c r="%s" s="%d"><v>%s</v></c>',
                        $referencia,
                        $estilo,
                        $this->numeroXml(
                            (float)
                            $valor
                        )
                    );
            } else {
                $xml .=
                    sprintf(
                        '<c r="%s" s="%d" t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
                        $referencia,
                        $estilo,
                        $this->xml(
                            (string)
                            $valor
                        )
                    );
            }
        }

        $xml .=
            '</row>';

        return $xml;
    }

    private function estiloEstadoExcel(
        string $estado
    ): int {
        return match (
            mb_strtolower(
                trim(
                    $estado
                )
            )
        ) {
            'entregada' => 10,
            'en tránsito',
            'en transito' => 11,
            'anulada' => 12,
            'registrada' => 13,
            default => 5,
        };
    }

    private function letraColumna(
        int $numero
    ): string {
        $letra =
            '';

        while (
            $numero >
            0
        ) {
            $numero--;

            $letra =
                chr(
                    65 +
                    ($numero % 26)
                ) .
                $letra;

            $numero =
                intdiv(
                    $numero,
                    26
                );
        }

        return $letra;
    }

    private function xml(
        string $valor
    ): string {
        return htmlspecialchars(
            $valor,
            ENT_XML1 |
            ENT_QUOTES,
            'UTF-8'
        );
    }

    private function numeroXml(
        float $valor
    ): string {
        return rtrim(
            rtrim(
                number_format(
                    $valor,
                    8,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PARTES XLSX
    |--------------------------------------------------------------------------
    */

    private function excelContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function excelRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function excelWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Encomiendas" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function excelWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function excelAppProps(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>CATUDRIVE</Application>'
            . '</Properties>';
    }

    private function excelCoreProps(): string
    {
        $fecha =
            now()
                ->utc()
                ->format(
                    'Y-m-d\\TH:i:s\\Z'
                );

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Reporte de Encomiendas</dc:title>'
            . '<dc:creator>CATUDRIVE</dc:creator>'
            . '<cp:lastModifiedBy>CATUDRIVE</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $fecha . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $fecha . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function excelStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Bs &quot;#,##0.00"/></numFmts>'
            . '<fonts count="6">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="18"/><color rgb="FF1F1F1F"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF1F1F1F"/><name val="Calibri"/></font>'
            . '<font><sz val="10"/><color rgb="FF666666"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FF1F1F1F"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="8">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFC107"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF2D2D2D"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF7EE"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF4D6"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFE7E7"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F1FF"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD9D9D9"/></left><right style="thin"><color rgb="FFD9D9D9"/></right><top style="thin"><color rgb="FFD9D9D9"/></top><bottom style="thin"><color rgb="FFD9D9D9"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="14">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="5" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="7" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
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
