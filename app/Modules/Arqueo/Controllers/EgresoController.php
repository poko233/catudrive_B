<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Controllers;

use App\Modules\Arqueo\Requests\ListarMovimientosRequest;
use App\Modules\Arqueo\Requests\StoreEgresoRequest;
use App\Modules\Arqueo\Resource\EgresoResource;
use App\Modules\Arqueo\Services\ArqueoService;
use App\Shared\Models\Egreso;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class EgresoController
{
    public function __construct(
        private readonly ArqueoService $arqueoService,
    ) {
    }

    public function index(ListarMovimientosRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();
        $perPage = (int) ($filtros['per_page'] ?? 15);

        $query = Egreso::query()
            ->with(['user', 'tipoTransaccion'])
            ->orderByDesc('fecha_registro');

        $query->where(
            'id_user',
            (int) ($filtros['id_user'] ?? $request->user()->id)
        );

        if (!empty($filtros['id_arqueo'])) {
            $query->where('id_arqueo', $filtros['id_arqueo']);
        }
        if (!empty($filtros['id_tipo_transaccion'])) {
            $query->where('id_tipo_transaccion', $filtros['id_tipo_transaccion']);
        }
        if (!empty($filtros['tipo_pago'])) {
            $query->where('tipo_pago', $filtros['tipo_pago']);
        }
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_registro', '>=', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_registro', '<=', $filtros['fecha_hasta']);
        }

        return EgresoResource::collection($query->paginate($perPage));
    }

    public function store(StoreEgresoRequest $request): EgresoResource
    {
        $datos = $request->validated();

        $egreso = $this->arqueoService->registrarEgreso(
            idUser: (int) $request->user()->id,
            tipoTransaccion: is_int($datos['tipo_transaccion'])
            ? $datos['tipo_transaccion']
            : (string) $datos['tipo_transaccion'],
            monto: (float) $datos['monto'],
            tipoPago: (string) $datos['tipo_pago'],
            detalle: (string) $datos['detalle'],
        );

        $egreso->load(['user', 'tipoTransaccion']);

        return new EgresoResource($egreso);
    }

    public function show(Egreso $egreso): EgresoResource
    {
        $egreso->load(['user', 'tipoTransaccion', 'arqueo']);

        return new EgresoResource($egreso);
    }

    public function anular(Egreso $egreso): EgresoResource
    {
        $egreso = $this->arqueoService->anularEgreso($egreso);

        return new EgresoResource($egreso);
    }
    public function comprobante(Egreso $egreso): Response
    {
        $html = $this->arqueoService->renderHtmlEgreso((int) $egreso->id);

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}