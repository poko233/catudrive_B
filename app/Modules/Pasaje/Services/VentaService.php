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

        // Obtener todos los detalles de venta activos para este viaje
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

        // Mapa id_asiento => datos de ocupación
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

            return $venta->load([
                'detalles.asiento',
                'detalles.pasajero',
            ]);
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
                // Si ya existe pasajero, actualizarlo
                $pasajero = Pasajero::query()->findOrFail($detalle->id_pasajero);
                $pasajero->update($datosPasajero);
            } else {
                // Crear nuevo pasajero y asociar
                $pasajero = Pasajero::query()->create($datosPasajero);
                $detalle->update(['id_pasajero' => $pasajero->id]);
            }
        });

        return $detalle->fresh('pasajero');
    }

    /**
     * Confirma la venta, asigna pasajeros a los detalles y cambia estado a Pagada.
     *
     * @param int $ventaId
     * @param string $formaPago
     * @param array $pasajeros Arreglo con id_detalle_venta y datos del pasajero.
     * @return Venta
     */
    public function confirmarVenta(int $ventaId, string $formaPago, array $pasajeros): Venta
    {
        $venta = Venta::query()->with('detalles')->findOrFail($ventaId);

        if ($venta->estado !== 'Pendiente') {
            throw new RuntimeException('Solo se puede confirmar una venta pendiente.');
        }

        DB::transaction(function () use ($venta, $formaPago, $pasajeros) {
            $detallesVenta = $venta->detalles->keyBy('id');

            // Validar pertenencia de detalles
            foreach ($pasajeros as $datosPasajero) {
                $detalleId = $datosPasajero['id_detalle_venta'];

                if (!$detallesVenta->has($detalleId)) {
                    throw new RuntimeException("El detalle {$detalleId} no pertenece a esta venta.");
                }
            }

            // Procesar cada pasajero y actualizar precios
            foreach ($pasajeros as $datosPasajero) {
                $detalleId = $datosPasajero['id_detalle_venta'];
                $detalle = $detallesVenta->get($detalleId);

                // Crear o actualizar pasajero
                $pasajeroData = [
                    'nombres' => $datosPasajero['nombres'],
                    'apellido_paterno' => $datosPasajero['apellido_paterno'],
                    'apellido_materno' => $datosPasajero['apellido_materno'] ?? null,
                    'ci' => $datosPasajero['ci'],
                ];

                if ($detalle->id_pasajero) {
                    $pasajero = Pasajero::query()->findOrFail($detalle->id_pasajero);
                    $pasajero->update($pasajeroData);
                } else {
                    $pasajero = Pasajero::query()->create($pasajeroData);
                }

                // Actualizar precio unitario si viene en el request
                $precioUnitario = isset($datosPasajero['precio_unitario'])
                    ? (float) $datosPasajero['precio_unitario']
                    : (float) $detalle->precio_unitario;

                $detalle->update([
                    'id_pasajero' => $pasajero->id,
                    'precio_unitario' => $precioUnitario,
                ]);
            }

            // Recalcular total de la venta
            $precioTotal = $venta->detalles()->sum('precio_unitario');

            // Actualizar venta
            $venta->update([
                'estado' => 'Pagada',
                'forma_pago' => $formaPago,
                'precio_total' => $precioTotal,
            ]);
        });

        return $venta->fresh()->load([
            'detalles.asiento',
            'detalles.pasajero',
            'viaje.vehiculoChoferRuta.ruta',
        ]);
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
    /**
     * Actualiza el estado de un viaje.
     */
    public function actualizarEstadoViaje(Viaje $viaje, string $estado): Viaje
    {
        $viaje->update(['estado' => $estado]);

        return $viaje->fresh();
    }
    public function generarQrData(int $ventaId): string
    {
        return $this->qrService->generateQrImage($ventaId);
    }
}