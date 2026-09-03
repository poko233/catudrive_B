<?php

declare(strict_types=1);

namespace App\Modules\Ruta\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RutaResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' =>
                (int)
                $this->id,

            'origen' =>
                (string)
                $this->origen,

            'destino' =>
                (string)
                $this->destino,

            /*
            |--------------------------------------------------------------------------
            | HORARIOS
            |--------------------------------------------------------------------------
            |
            | Entregamos exactamente el mismo formato que utiliza el formulario.
            |
            */

            'hora_inicio' =>
                $this->hora_inicio
                    ?->format(
                        'Y-m-d H:i'
                    ),

            'hora_fin' =>
                $this->hora_fin
                    ?->format(
                        'Y-m-d H:i'
                    ),

            'tarifa' =>
                (float)
                $this->tarifa,

            'estado' =>
                mb_strtoupper(
                    trim(
                        (string)
                        $this->estado
                    )
                ),

            /*
            |--------------------------------------------------------------------------
            | CANTIDAD DE VIAJES
            |--------------------------------------------------------------------------
            */

            'viajes_count' =>
                isset(
                    $this->viajes_count
                )
                    ? (int)
                    $this->viajes_count
                    : 0,

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }
}