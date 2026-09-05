<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsientoOcupacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fila' => $this->fila,
            'columna' => $this->columna,
            'tipo_celda' => $this->tipo_celda,
            'numero_asiento' => $this->numero_asiento,
            'estado' => $this->estado, // Activo/Inactivo
            'estado_ocupacion' => $this->estado_ocupacion, // libre, reservado, vendido, no_disponible
        ];
    }
}