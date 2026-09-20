<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Resource;

use App\Modules\Auth\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArqueoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_user' => $this->id_user,
            'fecha_apertura' => $this->fecha_apertura?->toIso8601String(),
            'fecha_cierre' => $this->fecha_cierre?->toIso8601String(),
            'saldo_anterior' => $this->saldo_anterior,

            'total_efectivo' => $this->total_efectivo,
            'total_tarjeta' => $this->total_tarjeta,
            'total_qr' => $this->total_qr,
            'total_transferencia' => $this->total_transferencia,
            'total_general' => $this->total_general,

            'billete_200' => $this->billete_200,
            'billete_100' => $this->billete_100,
            'billete_50' => $this->billete_50,
            'billete_20' => $this->billete_20,
            'billete_10' => $this->billete_10,
            'moneda_5' => $this->moneda_5,
            'moneda_2' => $this->moneda_2,
            'moneda_1' => $this->moneda_1,
            'moneda_50_ctvs' => $this->moneda_50_ctvs,
            'moneda_20_ctvs' => $this->moneda_20_ctvs,
            'moneda_10_ctvs' => $this->moneda_10_ctvs,

            'estado' => $this->estado,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'user' => new UserResource($this->whenLoaded('user')),
            'ingresos' => IngresoResource::collection($this->whenLoaded('ingresos')),
            'egresos' => EgresoResource::collection($this->whenLoaded('egresos')),
        ];
    }
}