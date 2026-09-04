<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaVehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categoria' => $this->categoria,
        ];
    }
}