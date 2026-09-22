<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Encomienda\Requests\AsignarEncomiendaRequest;
use App\Modules\Encomienda\Requests\CambiarEstadoEncomiendaRequest;
use App\Modules\Encomienda\Requests\EntregarEncomiendaRequest;
use App\Modules\Encomienda\Requests\EscanearEncomiendaQrRequest;
use App\Modules\Encomienda\Requests\ListarEncomiendasRequest;
use App\Modules\Encomienda\Requests\StoreEncomiendaRequest;
use App\Modules\Encomienda\Requests\UpdateEncomiendaRequest;
use App\Modules\Encomienda\Resource\EncomiendaResource;
use App\Modules\Encomienda\Services\EncomiendaService;
use App\Modules\Encomienda\Services\EncomiendaQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EncomiendaController extends Controller
{
    public function __construct(
        private readonly EncomiendaService $service,
        private readonly EncomiendaQrService $qrService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function index(
        ListarEncomiendasRequest $request
    ): JsonResponse {
        $items =
            $this->service
                ->listar(
                    $request->validated(),
                    (int) $request->input('per_page', 15)
                );

        return response()->json([
            'encomiendas' =>
                $items
                    ->getCollection()
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
                    ->values(),

            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],

            'resumen' =>
                $this->service
                    ->resumen(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CATÁLOGOS
    |--------------------------------------------------------------------------
    */

    public function catalogos(): JsonResponse
    {
        return response()->json(
            $this->service
                ->catalogos()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUSCAR POR GUÍA
    |--------------------------------------------------------------------------
    */

    public function buscarPorGuia(
        Request $request,
        string $guia
    ): JsonResponse {
        $item =
            $this->service
                ->buscarPorGuia(
                    $guia
                );

        return response()->json([
            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DETALLE
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->obtener(
                    $encomienda
                );

        return response()->json([
            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEncomiendaRequest $request
    ): JsonResponse {
        $item =
            $this->service
                ->crear(
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Encomienda registrada correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateEncomiendaRequest $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->actualizar(
                    $encomienda,
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Encomienda actualizada correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ASIGNAR
    |--------------------------------------------------------------------------
    */

    public function asignar(
        AsignarEncomiendaRequest $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->asignar(
                    $encomienda,
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Encomienda asignada al viaje correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CAMBIAR ESTADO LOGÍSTICO
    |--------------------------------------------------------------------------
    */

    public function cambiarEstado(
        CambiarEstadoEncomiendaRequest $request,
        int $encomienda
    ): JsonResponse {
        $item = $this->service->cambiarEstado(
            $encomienda,
            (string) $request->validated('estado')
        );

        return response()->json([
            'message' => 'Estado de la encomienda actualizado correctamente.',
            'encomienda' => (new EncomiendaResource($item))->resolve($request),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ENTREGAR
    |--------------------------------------------------------------------------
    */

    public function entregar(
        EntregarEncomiendaRequest $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->entregar(
                    $encomienda
                );

        return response()->json([
            'message' =>
                'Encomienda entregada correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | QR
    |--------------------------------------------------------------------------
    */

    public function qr(
        Request $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->obtener(
                    $encomienda
                );

        $contenido =
            $this->qrService
                ->contenido(
                    $item
                );

        return response()->json([
            'qr' => [
                'contenido' =>
                    $contenido,
                'imagen' =>
                    $this->qrService
                        ->imagen(
                            $contenido
                        ),
            ],
            'encomienda' =>
                (new EncomiendaResource($item))
                    ->resolve($request),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ESCANEAR QR
    |--------------------------------------------------------------------------
    */

    public function escanearQr(
        EscanearEncomiendaQrRequest $request
    ): JsonResponse {
        $token =
            $this->qrService
                ->tokenDesdeContenido(
                    (string) $request->validated('qr')
                );

        $item =
            $this->service
                ->buscarPorQr(
                    $token
                );

        return response()->json([
            'encomienda' =>
                (new EncomiendaResource($item))
                    ->resolve($request),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TICKET QR / COMPROBANTE
    |--------------------------------------------------------------------------
    */

    public function ticketQr(
        Request $request,
        int $encomienda
    ): Response {
        $item =
            $this->service
                ->obtener(
                    $encomienda
                );

        $contenido =
            $this->qrService
                ->contenido(
                    $item
                );

        $tipo =
            $request->query('tipo') === 'comprobante'
                ? 'comprobante'
                : 'etiqueta';

        return response()
            ->view(
                'encomiendas.ticket-thermal',
                [
                    'encomienda' =>
                        $item,
                    'qrImage' =>
                        $this->qrService
                            ->imagen(
                                $contenido
                            ),
                    'tipo' =>
                        $tipo,
                ]
            )
            ->header(
                'Content-Type',
                'text/html'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | ANULAR
    |--------------------------------------------------------------------------
    */

    public function anular(
        Request $request,
        int $encomienda
    ): JsonResponse {
        $item =
            $this->service
                ->anular(
                    $encomienda
                );

        return response()->json([
            'message' =>
                'Encomienda anulada correctamente.',

            'encomienda' =>
                (
                    new EncomiendaResource(
                        $item
                    )
                )->resolve(
                    $request
                ),
        ]);
    }
}