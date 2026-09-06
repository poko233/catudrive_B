<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsignacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_chofer' => $this->id_chofer,
            'id_vehiculo' => $this->id_vehiculo,
            'fecha_asignacion' => $this->fecha_asignacion?->format('Y-m-d'),
            'fecha_finalizacion' => $this->fecha_finalizacion?->format('Y-m-d'),
            'observacion' => $this->observacion,
            'estado' => $this->estado,

            // Chofer
            'chofer' => $this->whenLoaded('chofer', function () {
                $chofer = $this->chofer;
                $usuario = $chofer?->usuario;
                return [
                    'id' => $chofer?->id,
                    'nombre_completo' => trim(($usuario?->nombres ?? '') . ' ' . ($usuario?->primer_apellido ?? '') . ' ' . ($usuario?->segundo_apellido ?? '')),
                    'ci' => $usuario?->ci,
                    'foto' => $usuario?->foto,
                    'carnet_sindical' => $chofer?->carnet_sindical,
                    'numero_licencia' => $chofer?->numero_licencia,
                    'categoria_licencia' => $chofer?->categoria_licencia,
                ];
            }),

            // Vehículo
            'vehiculo' => $this->whenLoaded('vehiculo', function () {
                $vehiculo = $this->vehiculo;
                return [
                    'id' => $vehiculo?->id,
                    'placa' => $vehiculo?->placa,
                    'tipo' => $vehiculo?->tipo,
                    'marca' => $vehiculo?->marca,
                    'modelo' => $vehiculo?->modelo,
                    'color' => $vehiculo?->color,
                    'capacidad' => $vehiculo?->capacidad,
                    'estado' => $vehiculo?->estado,
                ];
            }),
        ];
    }
}