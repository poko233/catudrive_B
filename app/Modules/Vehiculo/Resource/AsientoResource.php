<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fila' => $this->fila,
            'columna' => $this->columna,
            'tipo_celda' => $this->tipo_celda,
            'numero_asiento' => $this->numero_asiento,
            'estado' => $this->estado,
        ];
    }
}