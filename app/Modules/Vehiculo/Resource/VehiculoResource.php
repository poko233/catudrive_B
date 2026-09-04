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
            'propietario' => $this->whenLoaded('propietario', function () {
                $propietario = $this->propietario;
                if (!$propietario)
                    return null;
                $chofer = $propietario->chofer;
                $usuario = $chofer?->usuario;
                return [
                    'id' => $propietario->id,
                    'id_chofer' => $propietario->id_chofer,
                    'nombre_completo' => trim(
                        implode(' ', array_filter([
                            $usuario?->nombres,
                            $usuario?->primer_apellido !== '-' ? $usuario?->primer_apellido : '',
                            $usuario?->segundo_apellido,
                        ]))
                    ),
                    'ci' => $usuario?->ci,
                    'carnet_sindical' => $chofer?->carnet_sindical,
                ];
            }),
        ];
    }
}