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
        | VIAJE ENCOMIENDA
        |--------------------------------------------------------------------------
        */

        $ruta =
            $this->ruta;

        $viajeEncomienda =
            $this
                ->viajeEncomienda;

        $viaje =
            $viajeEncomienda
                ?->viaje;

        $vehiculoChoferRuta =
            $viaje
                ?->vehiculoChoferRuta;

        $rutaViaje =
            $vehiculoChoferRuta
                ?->ruta;

        $asignacion =
            $vehiculoChoferRuta
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

            'id_ruta' =>
                $this->id_ruta
                    !== null
                        ? (int)
                        $this->id_ruta
                        : null,

            /*
            |--------------------------------------------------------------------------
            | COMPATIBILIDAD
            |--------------------------------------------------------------------------
            |
            | Estos campos ya no están físicamente en encomienda.
            | Se obtienen de la ruta relacionada.
            |
            */

            'origen' =>
                $ruta
                    ?->origen,

            'destino' =>
                $ruta
                    ?->destino,

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

            'remitente' =>
                $this->remitente,

            'destinatario' =>
                $this->destinatario,

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
                match (
                    $this->estado
                ) {
                    'Registrada' =>
                        'REGISTRADA',

                    'En tránsito' =>
                        'EN_TRANSITO',

                    'Entregada' =>
                        'ENTREGADA',

                    'Anulada' =>
                        'ANULADA',

                    default =>
                        mb_strtoupper(
                            str_replace(
                                ' ',
                                '_',
                                $this->estado
                            )
                        ),
                },

            'viaje' =>
                $viaje
                    ? [
                        'id' =>
                            (int)
                            $viaje->id,

                        'estado' =>
                            $viaje->estado,

                        'hora_inicio' =>
                            $vehiculoChoferRuta
                                ?->hora_inicio
                                ?->format(
                                    'Y-m-d H:i:s'
                                ),

                        'ruta' =>
                            $rutaViaje
                                ? [
                                    'id' =>
                                        (int)
                                        $rutaViaje->id,

                                    'origen' =>
                                        $rutaViaje->origen,

                                    'destino' =>
                                        $rutaViaje->destino,

                                    'estado' =>
                                        $rutaViaje->estado,
                                ]
                                : null,

                        'chofer' =>
                            $chofer
                                ? [
                                    'id' =>
                                        (int)
                                        $chofer->id,

                                    'nombre' =>
                                        trim(
                                            implode(
                                                ' ',
                                                array_filter([
                                                    $usuario
                                                        ?->nombres,

                                                    $usuario
                                                        ?->primer_apellido,

                                                    $usuario
                                                        ?->segundo_apellido,
                                                ])
                                            )
                                        ),

                                    'ci' =>
                                        $usuario
                                            ?->ci,

                                    'carnet_sindical' =>
                                        $chofer
                                            ?->carnet_sindical,
                                ]
                                : null,

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

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}