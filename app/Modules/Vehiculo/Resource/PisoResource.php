<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PisoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'nombre' => $this->nombre,
            'filas' => $this->filas,
            'columnas' => $this->columnas,
            'orden' => $this->orden,
            'estado' => $this->estado,
            'asientos' => AsientoResource::collection($this->whenLoaded('asientos')),
        ];
    }
}