<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EncomiendaResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        /*
        |--------------------------------------------------------------------------
        | ESTADO
        |--------------------------------------------------------------------------
        */

        $estadoDb =
            mb_strtoupper(
                trim(
                    (string)
                    $this->estado
                )
            );

        $estado =
            match ($estadoDb) {
                'REGISTRADA' =>
                    'REGISTRADA',

                'EN TRÁNSITO',
                'EN TRANSITO' =>
                    'EN_TRANSITO',

                'ENTREGADA' =>
                    'ENTREGADA',

                'ANULADA' =>
                    'ANULADA',

                default =>
                    'REGISTRADA',
            };

        /*
        |--------------------------------------------------------------------------
        | ASIGNACIÓN / VIAJE
        |--------------------------------------------------------------------------
        */

        $asignacionViaje =
            $this->asignacionViaje;

        $viaje =
            $asignacionViaje
                ?->viaje;

        $ruta =
            $viaje
                ?->ruta;

        $asignacion =
            $viaje
                ?->asignacion;

        $chofer =
            $asignacion
                ?->chofer;

        $usuario =
            $chofer
                ?->usuario;

        $vehiculo =
            $asignacion
                ?->vehiculo;

        $nombreChofer =
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

        return [
            /*
            |--------------------------------------------------------------------------
            | ENCOMIENDA
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int)
                $this->id,

            'guia' =>
                $this->guia,

            'fecha' =>
                $this->created_at
                    ?->format(
                        'Y-m-d'
                    ),

            'remitente' =>
                $this->remitente,

            'destinatario' =>
                $this->destinatario,

            'origen' =>
                $this->origen,

            'destino' =>
                $this->destino,

            'descripcion' =>
                $this->descripcion,

            'cantidad' =>
                (int)
                $this->cantidad,

            'precio' =>
                number_format(
                    (float)
                    $this->precio,
                    2,
                    '.',
                    ''
                ),

            'estado' =>
                $estado,

            /*
            |--------------------------------------------------------------------------
            | VIAJE
            |--------------------------------------------------------------------------
            */

            'viaje' =>
                $viaje
                    ? [
                        'id' =>
                            (int)
                            $viaje->id,

                        'hora_inicio' =>
                            $viaje->hora_inicio
                                ?->format(
                                    'Y-m-d H:i:s'
                                ),

                        /*
                        |--------------------------------------------------------------------------
                        | RUTA
                        |--------------------------------------------------------------------------
                        */

                        'ruta' =>
                            $ruta
                                ? [
                                    'id' =>
                                        (int)
                                        $ruta->id,

                                    'origen' =>
                                        $ruta->origen,

                                    'destino' =>
                                        $ruta->destino,

                                    'estado' =>
                                        $ruta->estado,
                                ]
                                : null,

                        /*
                        |--------------------------------------------------------------------------
                        | CHOFER
                        |--------------------------------------------------------------------------
                        */

                        'chofer' =>
                            $chofer
                                ? [
                                    'id' =>
                                        (int)
                                        $chofer->id,

                                    'nombre' =>
                                        $nombreChofer,

                                    'ci' =>
                                        $usuario?->ci,

                                    'carnet_sindical' =>
                                        $chofer->carnet_sindical,
                                ]
                                : null,

                        /*
                        |--------------------------------------------------------------------------
                        | VEHÍCULO
                        |--------------------------------------------------------------------------
                        */

                        'vehiculo' =>
                            $vehiculo
                                ? [
                                    'id' =>
                                        (int)
                                        $vehiculo->id,

                                    'placa' =>
                                        $vehiculo->placa,

                                    'tipo' =>
                                        $vehiculo->tipo,

                                    'marca' =>
                                        $vehiculo->marca,

                                    'modelo' =>
                                        $vehiculo->modelo,

                                    'color' =>
                                        $vehiculo->color,
                                ]
                                : null,
                    ]
                    : null,

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMPS
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }
}