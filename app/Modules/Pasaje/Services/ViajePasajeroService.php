<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\DetalleVenta;
use App\Shared\Models\Viaje;

class ViajePasajeroService
{
    public function __construct(
        private readonly ChoferContextService $choferContext,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | PASAJEROS DE UN VIAJE
    |--------------------------------------------------------------------------
    |
    | Devuelve únicamente pasajeros pertenecientes a ventas PAGADAS y
    | no eliminadas.
    |
    | Esto evita mostrar:
    |
    | - ventas anuladas
    | - ventas eliminadas
    | - reservas pendientes sin pasajero confirmado
    |
    | Cada registro representa:
    |
    | pasajero + asiento
    |
    */

    public function listar(
        int $idViaje
    ): array {
        /*
        |--------------------------------------------------------------------------
        | VIAJE + SEGURIDAD POR CHOFER
        |--------------------------------------------------------------------------
        */

        $viaje =
            Viaje::query()
                ->with([
                    'vehiculoChoferRuta.ruta',
                    'vehiculoChoferRuta.asignacion.vehiculo',
                    'vehiculoChoferRuta.asignacion.chofer.usuario',
                ])
                ->findOrFail(
                    $idViaje
                );

        $this->choferContext
            ->validarPertenece(
                $viaje
                    ->vehiculoChoferRuta
                    ?->asignacion
                    ?->id_chofer
                    !== null
                        ? (int)
                        $viaje
                            ->vehiculoChoferRuta
                            ?->asignacion
                            ?->id_chofer
                        : null
            );

        /*
        |--------------------------------------------------------------------------
        | DETALLES PAGADOS DEL VIAJE
        |--------------------------------------------------------------------------
        */

        $detalles =
            DetalleVenta::query()
                ->with([
                    'pasajero',
                    'asiento.piso',
                    'venta',
                ])
                ->whereNotNull(
                    'id_pasajero'
                )
                ->whereHas(
                    'venta',
                    function (
                        $query
                    ) use (
                        $idViaje
                    ): void {
                        $query
                            ->where(
                                'id_viaje',
                                $idViaje
                            )
                            ->where(
                                'estado',
                                'Pagada'
                            )
                            ->whereNull(
                                'deleted_at'
                            );
                    }
                )
                ->get()
                ->sortBy(
                    function (
                        DetalleVenta $detalle
                    ): int {
                        return
                            $detalle
                                ->asiento
                                ?->numero_asiento
                            ?? PHP_INT_MAX;
                    }
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | RESPUESTA
        |--------------------------------------------------------------------------
        */

        $pasajeros =
            $detalles
                ->map(
                    function (
                        DetalleVenta $detalle
                    ): array {
                        $pasajero =
                            $detalle->pasajero;

                        $asiento =
                            $detalle->asiento;

                        $nombreCompleto =
                            $pasajero
                                ? trim(
                                    preg_replace(
                                        '/\s+/u',
                                        ' ',
                                        implode(
                                            ' ',
                                            array_filter([
                                                $pasajero
                                                    ->nombres,

                                                $pasajero
                                                    ->apellido_paterno,

                                                $pasajero
                                                    ->apellido_materno,
                                            ])
                                        )
                                    )
                                    ?? ''
                                )
                                : '';

                        return [
                            'id_detalle_venta' =>
                                (int)
                                $detalle->id,

                            'id_venta' =>
                                (int)
                                $detalle->id_venta,

                            'pasajero' => [
                                'id' =>
                                    $pasajero
                                        ? (int)
                                        $pasajero->id
                                        : 0,

                                'nombre_completo' =>
                                    $nombreCompleto,

                                'nombres' =>
                                    $pasajero
                                        ?->nombres,

                                'apellido_paterno' =>
                                    $pasajero
                                        ?->apellido_paterno,

                                'apellido_materno' =>
                                    $pasajero
                                        ?->apellido_materno,

                                'ci' =>
                                    $pasajero
                                        ?->ci,
                            ],

                            'asiento' => [
                                'id' =>
                                    $asiento
                                        ? (int)
                                        $asiento->id
                                        : 0,

                                'numero_asiento' =>
                                    $asiento
                                        ?->numero_asiento,

                                'fila' =>
                                    $asiento
                                        ?->fila,

                                'columna' =>
                                    $asiento
                                        ?->columna,

                                'piso' =>
                                    $asiento
                                        ?->piso
                                        ?->nombre,
                            ],

                            'precio_unitario' =>
                                (float)
                                $detalle
                                    ->precio_unitario,
                        ];
                    }
                )
                ->values();

        $ruta =
            $viaje
                ->vehiculoChoferRuta
                ?->ruta;

        $asignacion =
            $viaje
                ->vehiculoChoferRuta
                ?->asignacion;

        $usuarioChofer =
            $asignacion
                ?->chofer
                ?->usuario;

        $nombreChofer =
            trim(
                implode(
                    ' ',
                    array_filter([
                        $usuarioChofer
                            ?->nombres,

                        $usuarioChofer
                            ?->primer_apellido,
                    ])
                )
            );

        return [
            'viaje' => [
                'id' =>
                    (int)
                    $viaje->id,

                'estado' =>
                    $viaje->estado,

                'origen' =>
                    $ruta
                        ?->origen,

                'destino' =>
                    $ruta
                        ?->destino,

                'hora_salida' =>
                    $viaje
                        ->vehiculoChoferRuta
                        ?->hora_inicio
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                'vehiculo' =>
                    $asignacion
                        ?->vehiculo
                        ?->placa,

                'chofer' =>
                    $nombreChofer !== ''
                        ? $nombreChofer
                        : null,
            ],

            'total' =>
                $pasajeros
                    ->count(),

            'pasajeros' =>
                $pasajeros,
        ];
    }
}
