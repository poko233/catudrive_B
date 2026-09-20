<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Controllers;

use App\Modules\Arqueo\Requests\ListarTiposTransaccionRequest;
use App\Modules\Arqueo\Requests\StoreTipoTransaccionRequest;
use App\Modules\Arqueo\Requests\UpdateTipoTransaccionRequest;
use App\Modules\Arqueo\Resource\TipoTransaccionResource;
use App\Modules\Arqueo\Services\TipoTransaccionService;
use App\Shared\Models\TipoTransaccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TipoTransaccionController
{
    public function __construct(
        private readonly TipoTransaccionService $service,
    ) {
    }

    public function index(ListarTiposTransaccionRequest $request): AnonymousResourceCollection
    {
        $tipos = $this->service->listar(
            $request->validated(),
            (int) ($request->validated()['per_page'] ?? 15),
        );

        return TipoTransaccionResource::collection($tipos);
    }

    public function store(StoreTipoTransaccionRequest $request): TipoTransaccionResource
    {
        $tipo = $this->service->crear($request->validated());

        return new TipoTransaccionResource($tipo);
    }

    public function show(TipoTransaccion $tipoTransaccion): TipoTransaccionResource
    {
        return new TipoTransaccionResource($tipoTransaccion);
    }

    public function update(
        UpdateTipoTransaccionRequest $request,
        TipoTransaccion $tipoTransaccion,
    ): TipoTransaccionResource {
        $tipo = $this->service->actualizar($tipoTransaccion, $request->validated());

        return new TipoTransaccionResource($tipo);
    }

    public function destroy(TipoTransaccion $tipoTransaccion): JsonResponse
    {
        $this->service->eliminar($tipoTransaccion);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de transacción eliminado correctamente.',
        ]);
    }
}