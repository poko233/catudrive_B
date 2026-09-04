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
                $this->origen,

            'destino' =>
                $this->destino,

            /*
            |--------------------------------------------------------------------------
            | FECHA / HORA INDEPENDIENTES
            |--------------------------------------------------------------------------
            */

            'fecha_inicio' =>
                $this->fecha_inicio
                    ?->format(
                        'Y-m-d'
                    ),

            'hora_inicio' =>
                $this->formatTime(
                    $this->hora_inicio
                ),

            'fecha_fin' =>
                $this->fecha_fin
                    ?->format(
                        'Y-m-d'
                    ),

            'hora_fin' =>
                $this->formatTime(
                    $this->hora_fin
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

            'viajes_count' =>
                (int)
                (
                    $this->viajes_count ??
                    0
                ),

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }

    private function formatTime(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        /*
         * PostgreSQL devuelve normalmente:
         *
         * 08:30:00
         *
         * El frontend solamente necesita:
         *
         * 08:30
         */

        $value =
            trim(
                (string)
                $value
            );

        return
            mb_strlen(
                $value
            ) >= 5
                ? mb_substr(
                    $value,
                    0,
                    5
                )
                : $value;
    }
}