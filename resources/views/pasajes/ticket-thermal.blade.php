<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Ticket #{{ $venta->id }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            width: 58mm;
            padding: 2mm;
            color: #000;
            background: #fff;
        }

        .ticket {
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        th,
        td {
            font-size: 9px;
            text-align: left;
            padding: 1px 0;
        }

        th {
            border-bottom: 1px solid #000;
        }

        .qr {
            text-align: center;
            margin: 4px 0;
        }

        .qr img {
            width: 100px;
            height: 100px;
        }

        .footer {
            text-align: center;
            margin-top: 3px;
            font-size: 8px;
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
            {{ $venta->viaje->vehiculoChoferRuta->ruta->destino ?? '-' }}</div>
        <div class="row"><span class="bold">Salida:</span>
            {{ $venta->viaje->vehiculoChoferRuta->hora_inicio?->format('d/m/Y H:i') ?? '-' }}</div>
        <div class="row"><span class="bold">Vehículo:</span>
            {{ $venta->viaje->vehiculoChoferRuta->asignacion->vehiculo->placa ?? '-' }}</div>
        <div class="row"><span class="bold">Chofer:</span>
            {{ trim(($venta->viaje->vehiculoChoferRuta->asignacion->chofer->usuario->nombres ?? '') . ' ' . ($venta->viaje->vehiculoChoferRuta->asignacion->chofer->usuario->primer_apellido ?? '')) }}
        </div>
        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Asiento</th>
                    <th>Pasajero</th>
                    <th>CI</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $detalle->asiento->numero_asiento ?? $detalle->asiento->fila . '-' . $detalle->asiento->columna }}
                        </td>
                        <td>
                            @if($detalle->pasajero)
                                {{ trim($detalle->pasajero->nombres . ' ' . $detalle->pasajero->apellido_paterno) }}
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