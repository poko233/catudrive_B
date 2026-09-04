<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_categoria' => $this->id_categoria,
            'placa' => $this->placa,
            'tipo' => $this->tipo,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'color' => $this->color,
            'capacidad' => $this->capacidad,
            'estado' => $this->estado,
            'categoria' => new CategoriaVehiculoResource($this->whenLoaded('categoria')),
            'pisos' => PisoResource::collection($this->whenLoaded('pisos')),
        ];
    }
}