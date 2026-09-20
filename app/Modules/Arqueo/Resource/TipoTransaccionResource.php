<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoTransaccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'transaccion' => $this->transaccion,
            'tipo_transaccion' => $this->tipo_transaccion,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}