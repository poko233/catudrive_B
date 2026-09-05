<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\Asiento;
use App\Shared\Models\DetalleVenta;
use App\Shared\Models\Pasajero;
use App\Shared\Models\Piso;
use App\Shared\Models\VehiculoChoferRuta;
use App\Shared\Models\Venta;
use App\Shared\Models\Viaje;
use App\Shared\Services\QrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VentaService
{
    public function __construct(
        private readonly QrService $qrService,
    ) {
    }

    /**
     * Lista viajes con filtros y orden (Vendiendo primero, luego En curso, resto).
     */
    public function listarViajes(array $filtros, int $perPage = 15)
    {
        $query = Viaje::query()
            ->with([
                'vehiculoChoferRuta.ruta',
                'vehiculoChoferRuta.asignacion.vehiculo',
                'vehiculoChoferRuta.asignacion.chofer.usuario',
            ])
            ->orderByRaw("CASE estado WHEN 'Vendiendo' THEN 0 WHEN 'En curso' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc');

        if (!empty($filtros['origen'])) {
            $query->whereHas('vehiculoChoferRuta.ruta', function ($q) use ($filtros) {
                $q->where('origen', 'like', '%' . $filtros['origen'] . '%');
            });
        }

        if (!empty($filtros['destino'])) {
            $query->whereHas('vehiculoChoferRuta.ruta', function ($q) use ($filtros) {
                $q->where('destino', 'like', '%' . $filtros['destino'] . '%');
            });
        }

        if (!empty($filtros['fecha'])) {
            $query->whereHas('vehiculoChoferRuta', function ($q) use ($filtros) {
                $q->whereDate('hora_inicio', $filtros['fecha']);
            });
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['vehiculo_id'])) {
            $query->whereHas('vehiculoChoferRuta.asignacion', function ($q) use ($filtros) {
                $q->where('id_vehiculo', $filtros['vehiculo_id']);
            });
        }

        if (!empty($filtros['chofer_id'])) {
            $query->whereHas('vehiculoChoferRuta.asignacion', function ($q) use ($filtros) {
                $q->where('id_chofer', $filtros['chofer_id']);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un viaje a partir de un vehiculo_chofer_ruta existente.
     */
    public function crearViaje(int $idVehiculoChoferRuta): Viaje
    {
        // Verificar que exista y esté activo?
        $vehiculoChoferRuta = VehiculoChoferRuta::query()->findOrFail($idVehiculoChoferRuta);

        return Viaje::query()->create([
            'id_vehiculo_chofer_ruta' => $idVehiculoChoferRuta,
            'estado' => 'Vendiendo',
        ]);
    }

    /**
     * Obtiene la estructura de asientos del vehículo para un viaje, con estado de ocupación.
     *
     * @return array{id_piso, nombre, asientos: array}
     */
    public function obtenerAsientos(int $idViaje): array
    {
        $viaje = Viaje::query()
            ->with([
                'vehiculoChoferRuta.asignacion.vehiculo.pisos.asientos',
            ])
            ->findOrFail($idViaje);

        $vehiculo = $viaje->vehiculoChoferRuta->asignacion->vehiculo;

        // Obtener asientos ocupados para este viaje (ventas Pendiente o Pagada)
        $asientosOcupados = DetalleVenta::query()
            ->join('venta', 'venta.id', '=', 'detalle_venta.id_venta')
            ->where('venta.id_viaje', $idViaje)
            ->whereIn('venta.estado', ['Pendiente', 'Pagada'])
            ->whereNull('venta.deleted_at')
            ->pluck('detalle_venta.id_asiento')
            ->all();

        $asientosReservados = DetalleVenta::query()
            ->join('venta', 'venta.id', '=', 'detalle_venta.id_venta')
            ->where('venta.id_viaje', $idViaje)
            ->where('venta.estado', 'Pendiente')
            ->whereNull('venta.deleted_at')
            ->pluck('detalle_venta.id_asiento')
            ->all();

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
                if ($asiento->tipo_celda !== 'pasajero') {
                    $estadoOcupacion = 'no_disponible';
                } elseif (in_array($asiento->id, $asientosReservados, true)) {
                    $estadoOcupacion = 'reservado';
                } elseif (in_array($asiento->id, $asientosOcupados, true)) {
                    $estadoOcupacion = 'vendido';
                }

                $pisoData['asientos'][] = [
                    'id' => $asiento->id,
                    'fila' => $asiento->fila,
                    'columna' => $asiento->columna,
                    'tipo_celda' => $asiento->tipo_celda,
                    'numero_asiento' => $asiento->numero_asiento,
                    'estado' => $asiento->estado,
                    'estado_ocupacion' => $estadoOcupacion,
                ];
            }

            $resultado[] = $pisoData;
        }

        return $resultado;
    }

    /**
     * Inicia una venta (reserva) con asientos seleccionados.
     * Crea venta Pendiente y detalles; bloquea asientos.
     */
    public function iniciarVenta(int $idViaje, array $asientos, int $userId): Venta
    {
        $viaje = Viaje::query()->findOrFail($idViaje);
        if ($viaje->estado !== 'Vendiendo') {
            throw new RuntimeException('El viaje no está en estado Vendiendo.');
        }

        return DB::transaction(function () use ($idViaje, $asientos, $userId) {
            // Verificar que los asientos estén libres
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
                $detalle = $venta->detalles()->create([
                    'id_asiento' => $asientoData['id_asiento'],
                    'precio_unitario' => $precio,
                    // id_pasajero se asigna después
                ]);
                $precioTotal += $precio;
            }

            $venta->update(['precio_total' => $precioTotal]);

            return $venta->load('detalles');
        });
    }

    /**
     * Asigna un pasajero a un detalle de venta.
     */
    public function asignarPasajero(int $detalleId, array $datosPasajero): DetalleVenta
    {
        $detalle = DetalleVenta::query()->findOrFail($detalleId);

        DB::transaction(function () use ($detalle, $datosPasajero) {
            $pasajero = Pasajero::query()->create($datosPasajero);
            $detalle->update(['id_pasajero' => $pasajero->id]);
        });

        return $detalle->fresh('pasajero');
    }

    /**
     * Confirma la venta (pasa a Pagada) y registra forma de pago.
     */
    public function confirmarVenta(int $ventaId, string $formaPago): Venta
    {
        $venta = Venta::query()->findOrFail($ventaId);

        if ($venta->estado !== 'Pendiente') {
            throw new RuntimeException('Solo se puede confirmar una venta pendiente.');
        }

        $venta->update([
            'estado' => 'Pagada',
            'forma_pago' => $formaPago,
        ]);

        return $venta->load('detalles.pasajero', 'viaje.vehiculoChoferRuta.ruta');
    }

    /**
     * Cancela una venta pendiente (reserva), liberando asientos.
     */
    public function cancelarVenta(int $ventaId): void
    {
        $venta = Venta::query()->findOrFail($ventaId);

        if ($venta->estado !== 'Pendiente') {
            throw new RuntimeException('Solo se puede cancelar una venta pendiente.');
        }

        DB::transaction(function () use ($venta) {
            $venta->detalles()->delete();
            $venta->update(['estado' => 'Anulada']);
        });
    }

    /**
     * Anula una venta pagada, liberando asientos.
     */
    public function anularVenta(int $ventaId): void
    {
        $venta = Venta::query()->findOrFail($ventaId);

        if ($venta->estado === 'Anulada') {
            throw new RuntimeException('La venta ya está anulada.');
        }

        DB::transaction(function () use ($venta) {
            $venta->detalles()->delete();
            $venta->update(['estado' => 'Anulada']);
            $venta->delete(); // soft delete
        });
    }

    /**
     * Elimina un detalle de venta (libera asiento). Si la venta queda sin detalles, se anula.
     */
    public function eliminarDetalle(int $detalleId): void
    {
        $detalle = DetalleVenta::query()->findOrFail($detalleId);
        $venta = $detalle->venta;

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
                // Recalcular precio total
                $nuevoTotal = $venta->detalles()->sum('precio_unitario');
                $venta->update(['precio_total' => $nuevoTotal]);
            }
        });
    }

    /**
     * Cambia el asiento de un detalle por otro libre.
     */
    public function cambiarAsiento(int $detalleId, int $nuevoAsientoId): DetalleVenta
    {
        $detalle = DetalleVenta::query()
            ->with('venta')
            ->findOrFail($detalleId);

        $venta = $detalle->venta;

        if ($venta->estado === 'Anulada') {
            throw new RuntimeException('No se puede cambiar asiento en una venta anulada.');
        }

        // Verificar que el nuevo asiento esté libre en el mismo viaje
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

    /**
     * Obtiene datos completos de una venta para reimpresión.
     */
    public function obtenerVenta(int $ventaId): Venta
    {
        return Venta::query()
            ->with([
                'detalles.pasajero',
                'detalles.asiento',
                'viaje.vehiculoChoferRuta.ruta',
                'viaje.vehiculoChoferRuta.asignacion.vehiculo',
                'viaje.vehiculoChoferRuta.asignacion.chofer.usuario',
            ])
            ->findOrFail($ventaId);
    }

    /**
     * Genera PDF del boleto de venta.
     */
    public function generarPdfVenta(int $ventaId): \Barryvdh\DomPDF\PDF
    {
        $venta = $this->obtenerVenta($ventaId);

        $qrData = $this->qrService->generateQrImage($ventaId);
        // QrService devuelve data URI; para PDF lo pasamos como imagen base64

        $pdf = Pdf::loadView('pasajes.ticket', [
            'venta' => $venta,
            'qrData' => $qrData,
        ]);

        return $pdf;
    }
}