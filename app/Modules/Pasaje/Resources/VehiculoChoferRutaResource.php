<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoChoferRutaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_asignacion_vehiculo_chofer' => $this->id_asignacion_vehiculo_chofer,
            'id_ruta' => $this->id_ruta,
            'hora_inicio' => $this->hora_inicio?->format('Y-m-d H:i:s'),
            'asignacion' => new AsignacionResource($this->whenLoaded('asignacion')),
            'ruta' => new RutaResource($this->whenLoaded('ruta')),
        ];
    }
}