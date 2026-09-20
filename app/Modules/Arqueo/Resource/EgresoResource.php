<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Resource;

use App\Modules\Auth\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EgresoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_user' => $this->id_user,
            'id_arqueo' => $this->id_arqueo,
            'id_tipo_transaccion' => $this->id_tipo_transaccion,
            'tipo_pago' => $this->tipo_pago,
            'fecha_registro' => $this->fecha_registro?->toIso8601String(),
            'detalle' => $this->detalle,
            'monto' => $this->monto,
            'estado' => $this->estado,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'user' => new UserResource($this->whenLoaded('user')),
            'tipo_transaccion' => new TipoTransaccionResource(
                $this->whenLoaded('tipoTransaccion')
            ),
        ];
    }
}