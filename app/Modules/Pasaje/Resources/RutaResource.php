<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RutaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'origen' => $this->origen,
            'destino' => $this->destino,
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'hora_inicio' => $this->hora_inicio,
            'tarifa' => $this->tarifa,
            'estado' => $this->estado,
            // Se omiten fecha_fin y hora_fin por requerimiento
        ];
    }
}