<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_viaje' => $this->id_viaje,
            'origen' => $this->viaje?->vehiculoChoferRuta?->ruta?->origen,
            'destino' => $this->viaje?->vehiculoChoferRuta?->ruta?->destino,
            'hora_salida' => $this->viaje?->vehiculoChoferRuta?->hora_inicio?->format('Y-m-d H:i:s'),
            'estado' => $this->estado,
            'forma_pago' => $this->forma_pago,
            'precio_total' => $this->precio_total,
            'detalles' => $this->detalles->map(function ($detalle) {
                return [
                    'id' => $detalle->id,
                    'asiento' => [
                        'id' => $detalle->asiento?->id,
                        'fila' => $detalle->asiento?->fila,
                        'columna' => $detalle->asiento?->columna,
                        'numero_asiento' => $detalle->asiento?->numero_asiento,
                    ],
                    'pasajero' => $detalle->pasajero ? [
                        'id' => $detalle->pasajero->id,
                        'nombres' => $detalle->pasajero->nombres,
                        'apellido_paterno' => $detalle->pasajero->apellido_paterno,
                        'apellido_materno' => $detalle->pasajero->apellido_materno,
                        'ci' => $detalle->pasajero->ci,
                    ] : null,
                    'precio_unitario' => $detalle->precio_unitario,
                ];
            }),
        ];
    }
}