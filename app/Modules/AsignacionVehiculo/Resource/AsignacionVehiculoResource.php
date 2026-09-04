<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsignacionVehiculoResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $usuario =
            $this->chofer
                ?->usuario;

        $nombre =
            trim(
                implode(
                    ' ',
                    array_filter([
                        $usuario?->nombres,
                        $usuario?->primer_apellido,
                        $usuario?->segundo_apellido,
                    ])
                )
            );

        $estado =
            mb_strtoupper(
                trim(
                    (string)
                    $this->estado
                )
            ) === 'ACTIVO'
                ? 'ACTIVO'
                : 'FINALIZADO';

        return [
            'id' =>
                (int)
                $this->id,

            /*
            |--------------------------------------------------------------------------
            | PERÍODO
            |--------------------------------------------------------------------------
            */

            'fecha_asignacion' =>
                $this->fecha_asignacion
                    ?->format(
                        'Y-m-d'
                    ),

            'fecha_finalizacion' =>
                $this->fecha_finalizacion
                    ?->format(
                        'Y-m-d'
                    ),

            /*
            |--------------------------------------------------------------------------
            | OBSERVACIÓN
            |--------------------------------------------------------------------------
            */

            'observacion' =>
                $this->observacion,

            /*
            |--------------------------------------------------------------------------
            | ESTADO
            |--------------------------------------------------------------------------
            */

            'estado' =>
                $estado,

            /*
            |--------------------------------------------------------------------------
            | CHOFER
            |--------------------------------------------------------------------------
            */

            'chofer' => [
                'id' =>
                    (int)
                    $this->id_chofer,

                'nombre' =>
                    $nombre,

                'ci' =>
                    $usuario?->ci,

                'carnet_sindical' =>
                    $this->chofer
                        ?->carnet_sindical,

                'telefono' =>
                    $usuario?->celular
                    ?: $usuario?->telefono,
            ],

            /*
            |--------------------------------------------------------------------------
            | VEHÍCULO
            |--------------------------------------------------------------------------
            */

            'vehiculo' => [
                'id' =>
                    (int)
                    $this->id_vehiculo,

                'placa' =>
                    $this->vehiculo
                        ?->placa,

                'tipo' =>
                    $this->vehiculo
                        ?->tipo,

                'marca' =>
                    $this->vehiculo
                        ?->marca,

                'modelo' =>
                    $this->vehiculo
                        ?->modelo,

                'color' =>
                    $this->vehiculo
                        ?->color,

                'capacidad' =>
                    $this->vehiculo
                        ?->capacidad,

                'estado' =>
                    $this->vehiculo
                        ?->estado,
            ],

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }
}