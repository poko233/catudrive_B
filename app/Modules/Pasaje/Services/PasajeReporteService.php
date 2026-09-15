<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\DetalleVenta;
use App\Shared\Models\Venta;
use App\Shared\Models\Viaje;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PasajeReporteService
{
    public function __construct(
        private readonly ChoferContextService $choferContext,
    ) {
    }

    /**
     * Dispatcher.
     */
    public function obtener(string $tipo, array $filtros): array
    {
        return match ($tipo) {
            'vendidos_por_fecha' => $this->vendidosPorFecha($filtros),
            'por_ruta' => $this->porRuta($filtros),
            'por_vehiculo' => $this->porVehiculo($filtros),
            'por_chofer' => $this->porChofer($filtros),
            'ingresos' => $this->ingresos($filtros),
            default => throw new \InvalidArgumentException(
                'El tipo de reporte de pasajes no es válido.'
            ),
        };
    }

    // ─────────────────────────────────────────────────────────────
    // REPORTES
    // ─────────────────────────────────────────────────────────────

    /** Pasajes vendidos por fecha. */
    public function vendidosPorFecha(array $filtros): array
    {
        $query = $this->queryVentasBase($filtros);

        $rows = $query
            ->select(
                DB::raw('DATE(venta.created_at) as fecha'),
                DB::raw('COUNT(DISTINCT venta.id) as total_ventas'),
                DB::raw('COUNT(detalle_venta.id) as total_asientos'),
                DB::raw('COALESCE(SUM(detalle_venta.precio_unitario), 0) as ingreso_total')
            )
            ->groupBy(DB::raw('DATE(venta.created_at)'))
            ->orderByDesc('fecha')
            ->get();

        return [
            'tipo' => 'vendidos_por_fecha',
            'titulo' => 'Pasajes vendidos por fecha',
            'items' => $rows,
            'total_registros' => $rows->count(),
            'total_ventas' => (int) $rows->sum('total_ventas'),
            'total_asientos' => (int) $rows->sum('total_asientos'),
            'ingreso_total' => (float) $rows->sum('ingreso_total'),
        ];
    }

    /** Pasajes por ruta. */
    public function porRuta(array $filtros): array
    {
        $query = $this->queryVentasBase($filtros)
            ->join('ruta', 'ruta.id', '=', 'vehiculo_chofer_ruta.id_ruta');

        if (!empty($filtros['id_ruta'])) {
            $query->where('ruta.id', (int) $filtros['id_ruta']);
        }

        $rows = $query
            ->select(
                'ruta.id as id_ruta',
                'ruta.origen',
                'ruta.destino',
                DB::raw('COUNT(DISTINCT venta.id) as total_ventas'),
                DB::raw('COUNT(detalle_venta.id) as total_asientos'),
                DB::raw('COALESCE(SUM(detalle_venta.precio_unitario), 0) as ingreso_total')
            )
            ->groupBy('ruta.id', 'ruta.origen', 'ruta.destino')
            ->orderByDesc('total_asientos')
            ->get();

        return [
            'tipo' => 'por_ruta',
            'titulo' => 'Pasajes por ruta',
            'items' => $rows,
            'total_registros' => $rows->count(),
            'total_asientos' => (int) $rows->sum('total_asientos'),
            'ingreso_total' => (float) $rows->sum('ingreso_total'),
        ];
    }

    /** Pasajes por vehículo. */
    public function porVehiculo(array $filtros): array
    {
        $query = $this->queryVentasBase($filtros)
            ->join('asignacion_vehiculo_chofer', 'asignacion_vehiculo_chofer.id', '=', 'vehiculo_chofer_ruta.id_asignacion_vehiculo_chofer')
            ->join('vehiculo', 'vehiculo.id', '=', 'asignacion_vehiculo_chofer.id_vehiculo');

        if (!empty($filtros['id_vehiculo'])) {
            $query->where('vehiculo.id', (int) $filtros['id_vehiculo']);
        }

        $rows = $query
            ->select(
                'vehiculo.id as id_vehiculo',
                'vehiculo.placa',
                'vehiculo.tipo',
                'vehiculo.marca',
                'vehiculo.modelo',
                DB::raw('COUNT(DISTINCT venta.id) as total_ventas'),
                DB::raw('COUNT(detalle_venta.id) as total_asientos'),
                DB::raw('COALESCE(SUM(detalle_venta.precio_unitario), 0) as ingreso_total')
            )
            ->groupBy('vehiculo.id', 'vehiculo.placa', 'vehiculo.tipo', 'vehiculo.marca', 'vehiculo.modelo')
            ->orderByDesc('total_asientos')
            ->get();

        return [
            'tipo' => 'por_vehiculo',
            'titulo' => 'Pasajes por vehículo',
            'items' => $rows,
            'total_registros' => $rows->count(),
            'total_asientos' => (int) $rows->sum('total_asientos'),
            'ingreso_total' => (float) $rows->sum('ingreso_total'),
        ];
    }

    /** Pasajes por chofer. */
    public function porChofer(array $filtros): array
    {
        $query = $this->queryVentasBase($filtros)
            ->join('asignacion_vehiculo_chofer', 'asignacion_vehiculo_chofer.id', '=', 'vehiculo_chofer_ruta.id_asignacion_vehiculo_chofer')
            ->join('chofer', 'chofer.id', '=', 'asignacion_vehiculo_chofer.id_chofer')
            ->join('user', 'user.id', '=', 'chofer.id');

        if (!empty($filtros['id_chofer'])) {
            $query->where('chofer.id', (int) $filtros['id_chofer']);
        }

        $rows = $query
            ->select(
                'chofer.id as id_chofer',
                'user.nombres',
                'user.primer_apellido',
                'user.segundo_apellido',
                'user.ci',
                'chofer.carnet_sindical',
                DB::raw('COUNT(DISTINCT venta.id) as total_ventas'),
                DB::raw('COUNT(detalle_venta.id) as total_asientos'),
                DB::raw('COALESCE(SUM(detalle_venta.precio_unitario), 0) as ingreso_total')
            )
            ->groupBy(
                'chofer.id',
                'user.nombres',
                'user.primer_apellido',
                'user.segundo_apellido',
                'user.ci',
                'chofer.carnet_sindical'
            )
            ->orderByDesc('total_asientos')
            ->get();

        return [
            'tipo' => 'por_chofer',
            'titulo' => 'Pasajes por chofer',
            'items' => $rows,
            'total_registros' => $rows->count(),
            'total_asientos' => (int) $rows->sum('total_asientos'),
            'ingreso_total' => (float) $rows->sum('ingreso_total'),
        ];
    }

    /** Ingresos por venta de pasajes (agrupado por forma de pago). */
    public function ingresos(array $filtros): array
    {
        $query = $this->queryVentasBase($filtros);

        if (!empty($filtros['forma_pago'])) {
            $query->where('venta.forma_pago', $filtros['forma_pago']);
        }

        $rows = $query
            ->select(
                DB::raw("COALESCE(venta.forma_pago, 'Sin especificar') as forma_pago"),
                DB::raw('COUNT(DISTINCT venta.id) as total_ventas'),
                DB::raw('COUNT(detalle_venta.id) as total_asientos'),
                DB::raw('COALESCE(SUM(detalle_venta.precio_unitario), 0) as ingreso_total')
            )
            ->groupBy('venta.forma_pago')
            ->orderByDesc('ingreso_total')
            ->get();

        return [
            'tipo' => 'ingresos',
            'titulo' => 'Ingresos por venta de pasajes',
            'items' => $rows,
            'total_registros' => $rows->count(),
            'total_ventas' => (int) $rows->sum('total_ventas'),
            'total_asientos' => (int) $rows->sum('total_asientos'),
            'ingreso_total' => (float) $rows->sum('ingreso_total'),
        ];
    }

    /** Planilla oficial de pasajeros para un viaje concreto. */
    public function planillaPasajeros(int $idViaje): array
    {
        $viaje = Viaje::query()
            ->with([
                'vehiculoChoferRuta.ruta',
                'vehiculoChoferRuta.asignacion.vehiculo',
                'vehiculoChoferRuta.asignacion.chofer.usuario',
                'vehiculoChoferRuta.asignacion',
            ])
            ->findOrFail($idViaje);

        // Aislamiento por chofer
        $idChofer = $this->choferContext->idChoferActual();
        if ($idChofer !== null) {
            $idChoferDelViaje = $viaje->vehiculoChoferRuta?->asignacion?->id_chofer;
            if ((int) $idChoferDelViaje !== $idChofer) {
                throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                    'No tienes acceso a este viaje.'
                );
            }
        }

        $detalles = DetalleVenta::query()
            ->with(['pasajero', 'asiento'])
            ->whereHas('venta', function (Builder $q) use ($idViaje): void {
                $q->where('id_viaje', $idViaje)
                    ->where('estado', 'Pagada')
                    ->whereNull('deleted_at');
            })
            ->get()
            ->sortBy(fn(DetalleVenta $d) => (int) ($d->asiento?->numero_asiento ?? 0))
            ->values();

        $totalAsientosVehiculo = (int) ($viaje->vehiculoChoferRuta->asignacion->vehiculo->capacidad ?? 0);
        $totalPasajeros = $detalles->count();
        $disponibles = max(0, $totalAsientosVehiculo - $totalPasajeros);

        return [
            'tipo' => 'planilla_pasajeros',
            'titulo' => 'Planilla de pasajeros para tránsito',
            'viaje' => $viaje,
            'items' => $detalles,
            'total_pasajeros' => $totalPasajeros,
            'total_asientos' => $totalAsientosVehiculo,
            'disponibles' => $disponibles,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Query base para todos los reportes analíticos.
     * Une venta + detalle_venta + viaje + vehiculo_chofer_ruta.
     * Por defecto solo ventas Pagadas (no anuladas).
     */
    private function queryVentasBase(array $filtros): Builder
    {
        $query = Venta::query()
            ->join('detalle_venta', 'detalle_venta.id_venta', '=', 'venta.id')
            ->join('viaje', 'viaje.id', '=', 'venta.id_viaje')
            ->join('vehiculo_chofer_ruta', 'vehiculo_chofer_ruta.id', '=', 'viaje.id_vehiculo_chofer_ruta')
            ->whereNull('venta.deleted_at');

        // Solo ventas Pagadas por defecto (salvo que el filtro pida otro estado)
        if (!empty($filtros['estado'])) {
            $query->where('venta.estado', $filtros['estado']);
        } else {
            $query->where('venta.estado', 'Pagada');
        }

        // Aislamiento por chofer
        $idChofer = $this->choferContext->idChoferActual();
        if ($idChofer !== null) {
            $query->join('asignacion_vehiculo_chofer as avc_scope', 'avc_scope.id', '=', 'vehiculo_chofer_ruta.id_asignacion_vehiculo_chofer')
                ->where('avc_scope.id_chofer', $idChofer);
        }

        // Filtro de fechas
        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('venta.created_at', '>=', $filtros['fecha_inicio']);
        }
        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('venta.created_at', '<=', $filtros['fecha_fin']);
        }

        return $query;
    }
}