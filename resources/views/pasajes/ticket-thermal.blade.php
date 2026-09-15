<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Ticket #{{ $venta->id }}</title>
    <style>
        @page {
            size: 48mm auto;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            font-size: 9px;
            width: 48mm;
            margin: 0 auto;
            padding: 0;
            color: #000;
            background: #fff;
            font-weight: 900;
            text-shadow:
                0 0 1.2px #000,
                0 0 0.5px #000;
            line-height: 1.2;
            position: relative;
            left: -1.25mm;
        }

        .ticket {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: 900;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 1px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0;
            font-weight: 900;
            text-shadow:
                0 0 1.2px #000,
                0 0 0.5px #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1px;
            font-weight: 900;
            text-shadow:
                0 0 1.2px #000,
                0 0 0.5px #000;
        }

        th,
        td {
            font-size: 8px;
            text-align: left;
            padding: 0 0 1px 0;
            font-weight: 900;
            text-shadow:
                0 0 1.2px #000,
                0 0 0.5px #000;
        }

        th {
            border-bottom: 1px solid #000;
        }

        .qr {
            text-align: center;
            margin: 2px 0;
        }

        .qr img {
            width: 100px;
            height: 100px;
        }

        .footer {
            text-align: center;
            margin-top: 1px;
            font-size: 7px;
            font-weight: 900;
            text-shadow:
                0 0 1.2px #000,
                0 0 0.5px #000;
        }
    </style>
</head>

<body>
    <div class="ticket">
        <div class="center bold">
            Catudrive
        </div>
        <div class="center">Comprobante #{{ $venta->id }}</div>
        <div class="line"></div>

        <div class="row"><span class="bold">Estado:</span> {{ $venta->estado }}</div>
        <div class="row"><span class="bold">Fecha:</span> {{ now()->format('d/m/Y H:i') }}</div>
        <div class="row"><span class="bold">Origen:</span> {{ $venta->viaje->vehiculoChoferRuta->ruta->origen ?? '-' }}
        </div>
        <div class="row"><span class="bold">Destino:</span>
            {{ $venta->viaje->vehiculoChoferRuta->ruta->destino ?? '-' }}
        </div>
        <div class="row"><span class="bold">Salida:</span>
            @php
                $ruta = $venta->viaje->vehiculoChoferRuta->ruta ?? null;
                $fechaSalida = $ruta?->fecha_inicio ? \Carbon\Carbon::parse($ruta->fecha_inicio)->format('d/m/Y') : null;
                $horaSalida = $ruta?->hora_inicio ? substr($ruta->hora_inicio, 0, 5) : null;
            @endphp
            {{ trim(($fechaSalida ?? '-') . ' ' . ($horaSalida ?? '')) }}
        </div>
        <div class="row"><span class="bold">Llegada:</span>
            @php
                $fechaLlegada = $ruta?->fecha_fin ? \Carbon\Carbon::parse($ruta->fecha_fin)->format('d/m/Y') : null;
                $horaLlegada = $ruta?->hora_fin ? substr($ruta->hora_fin, 0, 5) : null;
            @endphp
            {{ trim(($fechaLlegada ?? '-') . ' ' . ($horaLlegada ?? '')) }}
        </div>
        <div class="row"><span class="bold">Vehículo:</span>
            {{ $venta->viaje->vehiculoChoferRuta->asignacion->vehiculo->placa ?? '-' }}
        </div>
        <div class="row"><span class="bold">Chofer:</span>
            {{ trim(($venta->viaje->vehiculoChoferRuta->asignacion->chofer->usuario->nombres ?? '') . ' ' . ($venta->viaje->vehiculoChoferRuta->asignacion->chofer->usuario->primer_apellido ?? '')) }}
        </div>
        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th>Asiento</th>
                    <th>Pasajero</th>
                    <th>CI</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->asiento->numero_asiento ?? $detalle->asiento->fila . '-' . $detalle->asiento->columna }}
                        </td>
                        <td>
                            @if($detalle->pasajero)
                                {{ strtoupper($detalle->pasajero->apellido_paterno) }}
                                {{ strtoupper(mb_substr($detalle->pasajero->nombres, 0, 1)) }}.
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $detalle->pasajero->ci ?? '-' }}</td>
                        <td>Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="line"></div>
        <div class="row bold">
            <span>TOTAL</span>
            <span>Bs {{ number_format($venta->precio_total, 2) }}</span>
        </div>

        @if(isset($qrData))
            <div class="qr">
                <img src="{{ $qrData }}" alt="QR">
            </div>
        @endif

        <div class="footer">Gracias por su compra</div>
    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>

</html>