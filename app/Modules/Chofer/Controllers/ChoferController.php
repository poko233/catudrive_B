<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chofer\Requests\ActualizarFotoChoferRequest;
use App\Modules\Chofer\Requests\StoreChoferRequest;
use App\Modules\Chofer\Requests\UpdateChoferRequest;
use App\Modules\Chofer\Resource\ChoferResource;
use App\Modules\Chofer\Services\ChoferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChoferController extends Controller
{
    public function __construct(
        private readonly ChoferService $service
    ) {
    }

    public function index(
        Request $request
    ): JsonResponse {
        return response()->json([
            'choferes' =>
                $this->service
                    ->listar()
                    ->map(
                        fn($chofer) =>
                        (new ChoferResource(
                            $chofer
                        ))->resolve(
                                $request
                            )
                    )
                    ->values(),
        ]);
    }

    public function show(
        Request $request,
        int $chofer
    ): JsonResponse {
        return response()->json([
            'chofer' =>
                (new ChoferResource(
                    $this->service
                        ->obtener(
                            $chofer
                        )
                ))->resolve(
                        $request
                    ),
        ]);
    }

    public function store(
        StoreChoferRequest $request
    ): JsonResponse {
        $chofer =
            $this->service
                ->crear(
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Chofer registrado correctamente.',

            'chofer' =>
                (new ChoferResource(
                    $chofer
                ))->resolve(
                        $request
                    ),
        ], 201);
    }

    public function update(
        UpdateChoferRequest $request,
        int $chofer
    ): JsonResponse {
        $item =
            $this->service
                ->actualizar(
                    $chofer,
                    $request->validated()
                );

        return response()->json([
            'message' =>
                'Chofer actualizado correctamente.',

            'chofer' =>
                (new ChoferResource(
                    $item
                ))->resolve(
                        $request
                    ),
        ]);
    }

    /*
     * DELETE representa BAJA LÓGICA.
     */
    public function destroy(
        Request $request,
        int $chofer
    ): JsonResponse {
        $item =
            $this->service
                ->darDeBaja(
                    $chofer
                );

        return response()->json([
            'message' =>
                'Chofer dado de baja correctamente.',

            'chofer' =>
                (new ChoferResource(
                    $item
                ))->resolve(
                        $request
                    ),
        ]);
    }

    public function updatePhoto(
        ActualizarFotoChoferRequest $request,
        int $chofer
    ): JsonResponse {
        $item =
            $this->service
                ->actualizarFoto(
                    $chofer,
                    $request->file(
                        'foto'
                    )
                );

        return response()->json([
            'message' =>
                'Fotografía actualizada correctamente.',

            'chofer' =>
                (new ChoferResource(
                    $item
                ))->resolve(
                        $request
                    ),
        ]);
    }

    public function regenerarQr(
        Request $request,
        int $chofer
    ): JsonResponse {
        $item =
            $this->service
                ->regenerarQr(
                    $chofer
                );

        $resource =
            (new ChoferResource(
                $item
            ))->resolve(
                    $request
                );

        return response()->json([
            'message' =>
                'Código QR regenerado correctamente.',

            'codigo_qr' =>
                $resource[
                    'codigo_qr'
                ],

            'qrUrl' =>
                $resource[
                    'qrUrl'
                ],

            'chofer' =>
                $resource,
        ]);
    }

    public function historial(
        int $chofer
    ): JsonResponse {
        return response()->json([
            'historial' =>
                $this->service
                    ->historialAsignaciones(
                        $chofer
                    ),
        ]);
    }
    public function search(
        Request $request
    ): JsonResponse {
        $termino = (string) $request->query('search', '');

        return response()->json([
            'choferes' =>
                $this->service
                    ->buscar($termino)
                    ->map(
                        fn($chofer) =>
                        (new ChoferResource(
                            $chofer
                        ))->resolve(
                                $request
                            )
                    )
                    ->values(),
        ]);
    }
}