<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Boleto #{{ $venta->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        .ticket {
            width: 80mm;
            margin: 0 auto;
            border: 1px dashed #999;
            padding: 10px;
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #333;
            margin-bottom: 8px;
            padding-bottom: 6px;
        }

        .header h2 {
            margin: 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .label {
            font-weight: bold;
        }

        .qr {
            text-align: center;
            margin: 10px 0;
        }

        .qr img {
            width: 120px;
            height: 120px;
        }

        .footer {
            text-align: center;
            font-size: 9px;
            margin-top: 10px;
            border-top: 1px solid #333;
            padding-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 3px 5px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background: #f2f2f2;
        }
    </style>
</head>

<body>
    <div class="ticket">
        <div class="header">
            <h2>Boleto de Viaje</h2>
            <p>Comprobante #{{ $venta->id }}</p>
        </div>

        <div class="row"><span class="label">Estado:</span> {{ $venta->estado }}</div>
        <div class="row"><span class="label">Origen:</span> {{ $venta->viaje->vehiculoChoferRuta->ruta->origen ?? '-' }}
        </div>
        <div class="row"><span class="label">Destino:</span>
            {{ $venta->viaje->vehiculoChoferRuta->ruta->destino ?? '-' }}</div>
        <div class="row"><span class="label">Fecha/Hora salida:</span>
            {{ $venta->viaje->vehiculoChoferRuta->hora_inicio?->format('d/m/Y H:i') ?? '-' }}</div>
        <div class="row"><span class="label">Vehículo:</span>
            {{ $venta->viaje->vehiculoChoferRuta->asignacionVehiculoChofer->vehiculo->placa ?? '-' }}</div>
        <div class="row"><span class="label">Chofer:</span>
            {{ trim(($venta->viaje->vehiculoChoferRuta->asignacionVehiculoChofer->chofer->usuario->nombres ?? '') . ' ' . ($venta->viaje->vehiculoChoferRuta->asignacionVehiculoChofer->chofer->usuario->primer_apellido ?? '')) }}
        </div>
        <div class="row"><span class="label">Forma de pago:</span> {{ $venta->forma_pago ?? '-' }}</div>
        <div class="row"><span class="label">Total:</span> Bs {{ number_format($venta->precio_total, 2) }}</div>

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
                        <td>{{ trim($detalle->pasajero->nombres . ' ' . $detalle->pasajero->apellido_paterno . ' ' . $detalle->pasajero->apellido_materno) }}
                        </td>
                        <td>{{ $detalle->pasajero->ci ?? '-' }}</td>
                        <td>Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(isset($qrData))
            <div class="qr">
                <img src="{{ $qrData }}" alt="QR">
            </div>
        @endif

        <div class="footer">
            Gracias por su compra. Conserve este boleto.
        </div>
    </div>
</body>

</html>