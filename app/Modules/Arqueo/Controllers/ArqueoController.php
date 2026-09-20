<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Controllers;

use App\Modules\Arqueo\Requests\AbrirArqueoRequest;
use App\Modules\Arqueo\Requests\CerrarArqueoRequest;
use App\Modules\Arqueo\Requests\ListarArqueosRequest;
use App\Modules\Arqueo\Resource\ArqueoResource;
use App\Modules\Arqueo\Services\ArqueoService;
use App\Shared\Models\Arqueo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArqueoController
{
    public function __construct(
        private readonly ArqueoService $arqueoService,
    ) {
    }

    public function index(ListarArqueosRequest $request): AnonymousResourceCollection
    {
        $arqueos = $this->arqueoService->listarArqueos(
            $request->validated(),
            (int) $request->user()->id,
            (int) ($request->validated()['per_page'] ?? 15),
        );

        return ArqueoResource::collection($arqueos);
    }

    /**
     * Devuelve el arqueo abierto del usuario actual, o `data: null`
     * si no tiene ninguno. No responde 404: el front necesita poder
     * distinguir "no hay caja abierta" sin tratarlo como error.
     */
    public function abierto(Request $request): JsonResponse
    {
        $arqueo = $this->arqueoService->obtenerArqueoAbierto(
            (int) $request->user()->id
        );

        return response()->json([
            'data' => $arqueo ? new ArqueoResource($arqueo) : null,
        ]);
    }

    public function abrir(AbrirArqueoRequest $request): ArqueoResource
    {
        $arqueo = $this->arqueoService->abrirArqueo(
            (int) $request->user()->id,
            (float) $request->validated()['saldo_anterior'],
        );

        return new ArqueoResource($arqueo);
    }

    public function show(Arqueo $arqueo): ArqueoResource
    {
        $arqueo = $this->arqueoService->obtenerArqueoConDetalle((int) $arqueo->id);

        return new ArqueoResource($arqueo);
    }

    public function cerrar(CerrarArqueoRequest $request, Arqueo $arqueo): ArqueoResource
    {
        $arqueo = $this->arqueoService->cerrarArqueo(
            $arqueo,
            $request->validated(),
        );

        return new ArqueoResource($arqueo);
    }

    public function destroy(Arqueo $arqueo): JsonResponse
    {
        $this->arqueoService->eliminarArqueo($arqueo);

        return response()->json([
            'success' => true,
            'message' => 'Arqueo eliminado correctamente.',
        ]);
    }
}