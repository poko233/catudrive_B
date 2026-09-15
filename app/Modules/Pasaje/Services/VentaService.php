<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\DetalleVenta;
use App\Shared\Models\Pasajero;
use App\Shared\Models\VehiculoChoferRuta;
use App\Shared\Models\Venta;
use App\Shared\Models\Viaje;
use App\Shared\Services\QrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VentaService
{
    public function __construct(
        private readonly QrService $qrService,
        private readonly ChoferContextService $choferContext,
    ) {
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS DE AISLAMIENTO
    // ─────────────────────────────────────────────────────────────

    private function validarPropietarioViaje(Viaje $viaje): void
    {
        $idChofer = $this->choferContext->idChoferActual();
        if ($idChofer === null) {
            return;
        }

        $idChoferDelViaje = $viaje->vehiculoChoferRuta?->asignacion?->id_chofer;

        if ((int) $idChoferDelViaje !== $idChofer) {
            throw new AccessDeniedHttpException('No tienes acceso a este viaje.');
        }
    }

    private function validarPropietarioVenta(Venta $venta): void
    {
        $idChofer = $this->choferContext->idChoferActual();
        if ($idChofer === null) {
            return;
        }

        $idChoferDelViaje = $venta->viaje?->vehiculoChoferRuta?->asignacion?->id_chofer;

        if ((int) $idChoferDelViaje !== $idChofer) {
            throw new AccessDeniedHttpException('No tienes acceso a esta venta.');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // VIAJES
    // ─────────────────────────────────────────────────────────────

    public function listarViajes(array $filtros, int $perPage = 15)
    {
        $idChofer = $this->choferContext->idChoferActual();

        $query = Viaje::query()
            ->with([
                'vehiculoChoferRuta.ruta',
                'vehiculoChoferRuta.asignacion.vehiculo',
                'vehiculoChoferRuta.asignacion.chofer.usuario',
            ])
            ->orderByRaw("CASE estado WHEN 'Vendiendo' THEN 0 WHEN 'En curso' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc');

        if ($idChofer !== null) {
            $query->whereHas('vehiculoChoferRuta.asignacion', fn($q) => $q->where('id_chofer', $idChofer));
        }

        if (!empty($filtros['origen'])) {
            $query->whereHas('vehiculoChoferRuta.ruta', fn($q) => $q->where('origen', 'like', '%' . $filtros['origen'] . '%'));
        }

        if (!empty($filtros['destino'])) {
            $query->whereHas('vehiculoChoferRuta.ruta', fn($q) => $q->where('destino', 'like', '%' . $filtros['destino'] . '%'));
        }

        if (!empty($filtros['fecha'])) {
            $query->whereHas('vehiculoChoferRuta', fn($q) => $q->whereDate('hora_inicio', $filtros['fecha']));
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['vehiculo_id'])) {
            $query->whereHas('vehiculoChoferRuta.asignacion', fn($q) => $q->where('id_vehiculo', $filtros['vehiculo_id']));
        }

        if (!empty($filtros['chofer_id'])) {
            $query->whereHas('vehiculoChoferRuta.asignacion', fn($q) => $q->where('id_chofer', $filtros['chofer_id']));
        }

        return $query->paginate($perPage);
    }

    public function crearViaje(int $idVehiculoChoferRuta): Viaje
    {
        $vehiculoChoferRuta = VehiculoChoferRuta::query()
            ->with('asignacion')
            ->findOrFail($idVehiculoChoferRuta);

        $this->choferContext->validarPertenece(
            (int) $vehiculoChoferRuta->asignacion?->id_chofer
        );

        return Viaje::query()->create([
            'id_vehiculo_chofer_ruta' => $idVehiculoChoferRuta,
            'estado' => 'Vendiendo',
        ]);
    }

    public function actualizarEstadoViaje(Viaje $viaje, string $estado): Viaje
    {
        $viaje->loadMissing('vehiculoChoferRuta.asignacion');
        $this->validarPropietarioViaje($viaje);

        $viaje->update(['estado' => $estado]);

        return $viaje->fresh();
    }

    // ─────────────────────────────────────────────────────────────
    // ASIENTOS
    // ─────────────────────────────────────────────────────────────

    public function obtenerAsientos(int $idViaje): array
    {
        $viaje = Viaje::query()
            ->with([
                'vehiculoChoferRuta.asignacion.vehiculo.pisos.asientos',
                'vehiculoChoferRuta.asignacion',
            ])
            ->findOrFail($idViaje);

        $this->validarPropietarioViaje($viaje);

        $vehiculo = $viaje->vehiculoChoferRuta->asignacion->vehiculo;

        $detallesActivos = DetalleVenta::query()
            ->join('venta', 'venta.id', '=', 'detalle_venta.id_venta')
            ->where('venta.id_viaje', $idViaje)
            ->whereIn('venta.estado', ['Pendiente', 'Pagada'])
            ->whereNull('venta.deleted_at')
            ->select(
                'detalle_venta.id_asiento',
                'detalle_venta.id as id_detalle_venta',
                'venta.id as id_venta',
                'venta.estado as estado_venta'
            )
            ->get();

        $ocupaciones = [];
        foreach ($detallesActivos as $detalle) {
            $ocupaciones[$detalle->id_asiento] = [
                'id_venta' => $detalle->id_venta,
                'id_detalle_venta' => $detalle->id_detalle_venta,
                'estado_venta' => $detalle->estado_venta,
            ];
        }

        $resultado = [];

        foreach ($vehiculo->pisos as $piso) {
            $pisoData = [
                'id' => $piso->id,
                'nombre' => $piso->nombre,
                'numero' => $piso->numero,
                'asientos' => [],
            ];

            foreach ($piso->asientos as $asiento) {
                $estadoOcupacion = 'libre';
                $idVenta = null;
                $idDetalleVenta = null;

                if ($asiento->tipo_celda !== 'pasajero') {
                    $estadoOcupacion = 'no_disponible';
                } elseif (isset($ocupaciones[$asiento->id])) {
                    $estadoOcupacion = $ocupaciones[$asiento->id]['estado_venta'] === 'Pendiente'
                        ? 'reservado'
                        : 'vendido';
                    $idVenta = $ocupaciones[$asiento->id]['id_venta'];
                    $idDetalleVenta = $ocupaciones[$asiento->id]['id_detalle_venta'];
                }

                $pisoData['asientos'][] = [
                    'id' => $asiento->id,
                    'fila' => $asiento->fila,
                    'columna' => $asiento->columna,
                    'tipo_celda' => $asiento->tipo_celda,
                    'numero_asiento' => $asiento->numero_asiento,
                    'estado' => $asiento->estado,
                    'estado_ocupacion' => $estadoOcupacion,
                    'id_venta' => $idVenta,
                    'id_detalle_venta' => $idDetalleVenta,
                ];
            }

            $resultado[] = $pisoData;
        }

        return $resultado;
    }

    // ─────────────────────────────────────────────────────────────
    // VENTAS
    // ─────────────────────────────────────────────────────────────

    public function iniciarVenta(int $idViaje, array $asientos, int $userId): Venta
    {
        $viaje = Viaje::query()
            ->with('vehiculoChoferRuta.asignacion')
            ->findOrFail($idViaje);

        $this->validarPropietarioViaje($viaje);

        if ($viaje->estado !== 'Vendiendo') {
            throw new RuntimeException('El viaje no está en estado Vendiendo.');
        }

        return DB::transaction(function () use ($idViaje, $asientos, $userId) {
            foreach ($asientos as $asientoData) {
                $asientoId = $asientoData['id_asiento'];
                $ocupado = DetalleVenta::query()
                    ->join('venta', 'venta.id', '=', 'detalle_venta.id_venta')
                    ->where('venta.id_viaje', $idViaje)
                    ->whereIn('venta.estado', ['Pendiente', 'Pagada'])
                    ->whereNull('venta.deleted_at')
                    ->where('detalle_venta.id_asiento', $asientoId)
                    ->exists();

                if ($ocupado) {
                    throw new RuntimeException("El asiento {$asientoId} ya está ocupado.");
                }
            }

            $venta = Venta::query()->create([
                'id_viaje' => $idViaje,
                'id_user' => $userId,
                'estado' => 'Pendiente',
                'precio_total' => 0,
            ]);

            $precioTotal = 0;
            foreach ($asientos as $asientoData) {
                $precio = (float) $asientoData['precio_unitario'];
                $venta->detalles()->create([
                    'id_asiento' => $asientoData['id_asiento'],
                    'precio_unitario' => $precio,
                ]);
                $precioTotal += $precio;
            }

            $venta->update(['precio_total' => $precioTotal]);

            return $venta->load(['detalles.asiento', 'detalles.pasajero']);
        });
    }
    /**
     * Asigna o actualiza un pasajero en un detalle de venta.
     */
    public function asignarPasajero(int $detalleId, array $datosPasajero): DetalleVenta
    {
        $detalle = DetalleVenta::query()->findOrFail($detalleId);

        DB::transaction(function () use ($detalle, $datosPasajero) {
            if ($detalle->id_pasajero) {
                $pasajero = Pasajero::query()->findOrFail($detalle->id_pasajero);
                $pasajero->update($datosPasajero);
            } else {
                $pasajero = Pasajero::query()->create($datosPasajero);
                $detalle->update(['id_pasajero' => $pasajero->id]);
            }
        });

        return $detalle->fresh('pasajero');
    }
    public function confirmarVenta(int $ventaId, string $formaPago, array $pasajeros): Venta
    {
        $venta = Venta::query()
            ->with(['detalles', 'viaje.vehiculoChoferRuta.asignacion'])
            ->findOrFail($ventaId);

        $this->validarPropietarioVenta($venta);

        if ($venta->estado !== 'Pendiente') {
            throw new RuntimeException('Solo se puede confirmar una venta pendiente.');
        }

        DB::transaction(function () use ($venta, $formaPago, $pasajeros) {
            $detallesVenta = $venta->detalles->keyBy('id');

            foreach ($pasajeros as $datosPasajero) {
                if (!$detallesVenta->has($datosPasajero['id_detalle_venta'])) {
                    throw new RuntimeException("El detalle {$datosPasajero['id_detalle_venta']} no pertenece a esta venta.");
                }
            }

            foreach ($pasajeros as $datosPasajero) {
                $detalle = $detallesVenta->get($datosPasajero['id_detalle_venta']);

                $pasajeroData = [
                    'nombres' => $datosPasajero['nombres'],
                    'apellido_paterno' => $datosPasajero['apellido_paterno'],
                    'apellido_materno' => $datosPasajero['apellido_materno'] ?? null,
                    'ci' => $datosPasajero['ci'] ?? null,
                ];

                if ($detalle->id_pasajero) {
                    $pasajero = Pasajero::query()->findOrFail($detalle->id_pasajero);
                    $pasajero->update($pasajeroData);
                } else {
                    $pasajero = Pasajero::query()->create($pasajeroData);
                }

                $precioUnitario = isset($datosPasajero['precio_unitario'])
                    ? (float) $datosPasajero['precio_unitario']
                    : (float) $detalle->precio_unitario;

                $detalle->update([
                    'id_pasajero' => $pasajero->id,
                    'precio_unitario' => $precioUnitario,
                ]);
            }

            $venta->update([
                'estado' => 'Pagada',
                'forma_pago' => $formaPago,
                'precio_total' => $venta->detalles()->sum('precio_unitario'),
            ]);
        });

        return $venta->fresh()->load([
            'detalles.asiento',
            'detalles.pasajero',
            'viaje.vehiculoChoferRuta.ruta',
        ]);
    }

    public function cancelarVenta(int $ventaId): void
    {
        $venta = Venta::query()
            ->with('viaje.vehiculoChoferRuta.asignacion')
            ->findOrFail($ventaId);

        $this->validarPropietarioVenta($venta);

        if ($venta->estado !== 'Pendiente') {
            throw new RuntimeException('Solo se puede cancelar una venta pendiente.');
        }

        DB::transaction(function () use ($venta) {
            $venta->detalles()->delete();
            $venta->update(['estado' => 'Anulada']);
        });
    }

    public function anularVenta(int $ventaId): void
    {
        $venta = Venta::query()
            ->with('viaje.vehiculoChoferRuta.asignacion')
            ->findOrFail($ventaId);

        $this->validarPropietarioVenta($venta);

        if ($venta->estado === 'Anulada') {
            throw new RuntimeException('La venta ya está anulada.');
        }

        DB::transaction(function () use ($venta) {
            $venta->detalles()->delete();
            $venta->update(['estado' => 'Anulada']);
            $venta->delete();
        });
    }

    public function eliminarDetalle(int $detalleId): void
    {
        $detalle = DetalleVenta::query()
            ->with('venta.viaje.vehiculoChoferRuta.asignacion')
            ->findOrFail($detalleId);

        $venta = $detalle->venta;

        $this->validarPropietarioVenta($venta);

        if ($venta->estado === 'Anulada') {
            throw new RuntimeException('La venta ya está anulada.');
        }

        DB::transaction(function () use ($detalle, $venta) {
            $detalle->delete();

            $restantes = $venta->detalles()->count();
            if ($restantes === 0) {
                $venta->update(['estado' => 'Anulada']);
                $venta->delete();
            } else {
                $venta->update(['precio_total' => $venta->detalles()->sum('precio_unitario')]);
            }
        });
    }

    public function cambiarAsiento(int $detalleId, int $nuevoAsientoId): DetalleVenta
    {
        $detalle = DetalleVenta::query()
            ->with('venta.viaje.vehiculoChoferRuta.asignacion')
            ->findOrFail($detalleId);

        $venta = $detalle->venta;

        $this->validarPropietarioVenta($venta);

        if ($venta->estado === 'Anulada') {
            throw new RuntimeException('No se puede cambiar asiento en una venta anulada.');
        }

        $ocupado = DetalleVenta::query()
            ->join('venta', 'venta.id', '=', 'detalle_venta.id_venta')
            ->where('venta.id_viaje', $venta->id_viaje)
            ->whereIn('venta.estado', ['Pendiente', 'Pagada'])
            ->whereNull('venta.deleted_at')
            ->where('detalle_venta.id_asiento', $nuevoAsientoId)
            ->where('detalle_venta.id', '!=', $detalle->id)
            ->exists();

        if ($ocupado) {
            throw new RuntimeException('El asiento seleccionado ya está ocupado.');
        }

        $detalle->update(['id_asiento' => $nuevoAsientoId]);

        return $detalle->fresh('asiento');
    }

    // ─────────────────────────────────────────────────────────────
    // REIMPRESIÓN / PDF
    // ─────────────────────────────────────────────────────────────

    public function obtenerVenta(int $ventaId): Venta
    {
        $venta = Venta::query()
            ->with([
                'detalles.pasajero',
                'detalles.asiento',
                'viaje.vehiculoChoferRuta.ruta',
                'viaje.vehiculoChoferRuta.asignacion.vehiculo',
                'viaje.vehiculoChoferRuta.asignacion.chofer.usuario',
                'viaje.vehiculoChoferRuta.asignacion',
            ])
            ->findOrFail($ventaId);

        $this->validarPropietarioVenta($venta);

        return $venta;
    }

    public function generarPdfVenta(int $ventaId): \Barryvdh\DomPDF\PDF
    {
        $venta = $this->obtenerVenta($ventaId);
        $qrData = $this->qrService->generateQrImage($ventaId);

        return Pdf::loadView('pasajes.ticket', [
            'venta' => $venta,
            'qrData' => $qrData,
        ]);
    }

    public function generarQrData(int $ventaId): string
    {
        return $this->qrService->generateQrImage($ventaId);
    }
}