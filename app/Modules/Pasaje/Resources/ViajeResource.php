<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViajeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'id_vehiculo_chofer_ruta' => $this->id_vehiculo_chofer_ruta,
            'origen' => $this->vehiculoChoferRuta?->ruta?->origen,
            'destino' => $this->vehiculoChoferRuta?->ruta?->destino,
            'hora_salida' => $this->vehiculoChoferRuta?->hora_inicio?->format('Y-m-d H:i:s'),
            'tarifa' => $this->vehiculoChoferRuta?->ruta?->tarifa,
            'vehiculo' => $this->vehiculoChoferRuta?->asignacion?->vehiculo?->placa,
            'chofer' => $this->vehiculoChoferRuta?->asignacion?->chofer?->usuario?->nombres . ' ' . $this->vehiculoChoferRuta?->asignacion?->chofer?->usuario?->primer_apellido,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}