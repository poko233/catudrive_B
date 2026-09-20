<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Controllers;

use App\Modules\Arqueo\Requests\ListarMovimientosRequest;
use App\Modules\Arqueo\Requests\StoreIngresoRequest;
use App\Modules\Arqueo\Resource\IngresoResource;
use App\Modules\Arqueo\Services\ArqueoService;
use App\Shared\Models\Ingreso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class IngresoController
{
    public function __construct(
        private readonly ArqueoService $arqueoService,
    ) {
    }

    public function index(ListarMovimientosRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();
        $perPage = (int) ($filtros['per_page'] ?? 15);

        $query = Ingreso::query()
            ->with(['user', 'tipoTransaccion'])
            ->orderByDesc('fecha_registro');

        // Un usuario normal solo ve sus propios ingresos; si el
        // filtro `id_user` está presente, se respeta (para admin).
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

        return IngresoResource::collection($query->paginate($perPage));
    }

    public function store(StoreIngresoRequest $request): IngresoResource
    {
        $datos = $request->validated();

        $ingreso = $this->arqueoService->registrarIngreso(
            idUser: (int) $request->user()->id,
            tipoTransaccion: $this->normalizarTipoTransaccion($datos['tipo_transaccion']),
            monto: (float) $datos['monto'],
            tipoPago: (string) $datos['tipo_pago'],
            detalle: (string) $datos['detalle'],
        );

        $ingreso->load(['user', 'tipoTransaccion']);

        return new IngresoResource($ingreso);
    }

    public function show(Ingreso $ingreso): IngresoResource
    {
        $ingreso->load(['user', 'tipoTransaccion', 'arqueo']);

        return new IngresoResource($ingreso);
    }

    public function anular(Ingreso $ingreso): IngresoResource
    {
        $ingreso = $this->arqueoService->anularIngreso($ingreso);

        return new IngresoResource($ingreso);
    }
    /**
     * Devuelve el comprobante HTML del ingreso.
     *
     * El front lo consume como texto plano (`Content-Type: text/html`)
     * y decide si lo muestra en iframe, lo imprime, o lo convierte
     * a PDF con la librería que prefiera.
     */
    public function comprobante(Ingreso $ingreso): Response
    {
        $html = $this->arqueoService->renderHtmlIngreso((int) $ingreso->id);

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
    /**
     * Normaliza el valor recibido del request para el parámetro
     * `int|string $tipoTransaccion`. Acepta:
     *  - int    → ID directo
     *  - string numérico → se envía como string (código)
     *  - string no numérico → código
     */
    private function normalizarTipoTransaccion(mixed $valor): int|string
    {
        if (is_int($valor)) {
            return $valor;
        }

        if (is_numeric($valor) && is_string($valor)) {
            // Regla del ArqueoService: string siempre es código.
            return $valor;
        }

        return (string) $valor;
    }
}