<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\Viaje;

class ViajeEncomiendaService
{
    public function __construct(
        private readonly ChoferContextService $choferContext,
    ) {
    }

    public function listar(int $idViaje): array
    {
        $viaje = Viaje::query()
            ->with([
                'vehiculoChoferRuta.ruta',
                'vehiculoChoferRuta.asignacion.vehiculo',
                'vehiculoChoferRuta.asignacion.chofer.usuario',
                'encomiendas.remitente',
                'encomiendas.destinatario',
                'encomiendas.detalles',
            ])
            ->findOrFail($idViaje);

        $this->choferContext->validarPertenece(
            $viaje->vehiculoChoferRuta?->asignacion?->id_chofer !== null
                ? (int) $viaje->vehiculoChoferRuta?->asignacion?->id_chofer
                : null
        );

        $encomiendas = $viaje->encomiendas
            ->sortByDesc('id')
            ->values()
            ->map(function ($e): array {
                $nombre = static function ($cliente): string {
                    if (!$cliente) return '';
                    return trim(implode(' ', array_filter([
                        $cliente->nombres,
                        $cliente->apellido_paterno,
                        $cliente->apellido_materno,
                    ])));
                };

                return [
                    'id' => (int) $e->id,
                    'guia' => $e->guia,
                    'concepto' => $e->concepto,
                    'subtotal' => (float) $e->subtotal,
                    'descuento' => (float) $e->descuento,
                    'total' => (float) $e->total,
                    'lugar_pago' => $e->lugar_pago,
                    'estado_pago' => $e->estado_pago,
                    'tipo_pago' => $e->tipo_pago,
                    'estado' => $e->estado,
                    'remitente' => [
                        'id' => (int) ($e->remitente?->id ?? 0),
                        'nombre_completo' => $nombre($e->remitente),
                        'ci' => $e->remitente?->ci,
                        'telefono' => $e->remitente?->telefono,
                    ],
                    'destinatario' => [
                        'id' => (int) ($e->destinatario?->id ?? 0),
                        'nombre_completo' => $nombre($e->destinatario),
                        'ci' => $e->destinatario?->ci,
                        'telefono' => $e->destinatario?->telefono,
                    ],
                    'detalles' => $e->detalles->map(fn ($d) => [
                        'id' => (int) $d->id,
                        'detalle' => $d->detalle,
                        'cantidad' => (int) $d->cantidad,
                        'precio_unitario' => (float) $d->precio_unitario,
                    ])->values(),
                ];
            });

        $ruta = $viaje->vehiculoChoferRuta?->ruta;
        $asignacion = $viaje->vehiculoChoferRuta?->asignacion;
        $usuario = $asignacion?->chofer?->usuario;
        $chofer = trim(implode(' ', array_filter([
            $usuario?->nombres,
            $usuario?->primer_apellido,
            $usuario?->segundo_apellido,
        ])));

        return [
            'viaje' => [
                'id' => (int) $viaje->id,
                'estado' => $viaje->estado,
                'origen' => $ruta?->origen,
                'destino' => $ruta?->destino,
                'hora_salida' => $viaje->vehiculoChoferRuta?->hora_inicio?->format('Y-m-d H:i:s'),
                'vehiculo' => $asignacion?->vehiculo?->placa,
                'chofer' => $chofer !== '' ? $chofer : null,
            ],
            'total' => $encomiendas->count(),
            'encomiendas' => $encomiendas,
        ];
    }
}
