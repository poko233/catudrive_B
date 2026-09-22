<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <title>
        Boleto #{{ $venta->id }}
    </title>

    <style>
        body {
            font-family:
                DejaVu Sans,
                sans-serif;

            /*
            |--------------------------------------------------------------------------
            | ANTES: 12px
            |--------------------------------------------------------------------------
            */

            font-size: 15px;

            color: #111;

            line-height: 1.35;

            margin: 0;

            padding: 0;
        }

        .ticket {
            width: 80mm;

            margin:
                0 auto;

            border:
                1px dashed #999;

            /*
            |--------------------------------------------------------------------------
            | ANTES: 10px
            |--------------------------------------------------------------------------
            */

            padding: 14px;
        }

        /*
        |--------------------------------------------------------------------------
        | CABECERA
        |--------------------------------------------------------------------------
        */

        .header {
            text-align:
                center;

            border-bottom:
                1px solid #333;

            margin-bottom:
                12px;

            padding-bottom:
                10px;
        }

        .header h2 {
            margin:
                0;

            font-size:
                22px;

            line-height:
                1.2;
        }

        .header p {
            margin:
                5px 0 0 0;

            font-size:
                14px;
        }

        /*
        |--------------------------------------------------------------------------
        | FILAS
        |--------------------------------------------------------------------------
        */

        .row {
            display:
                flex;

            justify-content:
                space-between;

            gap:
                10px;

            /*
            |--------------------------------------------------------------------------
            | ANTES: 4px
            |--------------------------------------------------------------------------
            */

            margin-bottom:
                7px;

            font-size:
                14px;

            line-height:
                1.35;
        }

        .label {
            font-weight:
                bold;
        }

        /*
        |--------------------------------------------------------------------------
        | QR
        |--------------------------------------------------------------------------
        */

        .qr {
            text-align:
                center;

            margin:
                15px 0;
        }

        .qr img {
            /*
            |--------------------------------------------------------------------------
            | ANTES: 120px
            |--------------------------------------------------------------------------
            */

            width:
                155px;

            height:
                155px;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer {
            text-align:
                center;

            /*
            |--------------------------------------------------------------------------
            | ANTES: 9px
            |--------------------------------------------------------------------------
            */

            font-size:
                12px;

            margin-top:
                14px;

            border-top:
                1px solid #333;

            padding-top:
                8px;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLA
        |--------------------------------------------------------------------------
        */

        table {
            width:
                100%;

            border-collapse:
                collapse;

            margin-top:
                12px;
        }

        th,
        td {
            border:
                1px solid #ccc;

            /*
            |--------------------------------------------------------------------------
            | ANTES: 3px 5px
            |--------------------------------------------------------------------------
            */

            padding:
                6px 6px;

            text-align:
                left;

            /*
            |--------------------------------------------------------------------------
            | ANTES: 10px
            |--------------------------------------------------------------------------
            */

            font-size:
                12px;

            line-height:
                1.3;
        }

        th {
            background:
                #f2f2f2;

            font-weight:
                bold;
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        .total {
            font-size:
                17px;

            font-weight:
                bold;
        }
    </style>
</head>

<body>

    <div class="ticket">

        <div class="header">

            <h2>
                Boleto de Viaje
            </h2>

            <p>
                Comprobante #{{ $venta->id }}
            </p>

        </div>

        <div class="row">
            <span class="label">
                Estado:
            </span>

            <span>
                {{ $venta->estado }}
            </span>
        </div>

        <div class="row">
            <span class="label">
                Origen:
            </span>

            <span>
                {{
                    $venta
                        ->viaje
                        ->vehiculoChoferRuta
                        ->ruta
                        ->origen
                    ?? '-'
                }}
            </span>
        </div>

        <div class="row">
            <span class="label">
                Destino:
            </span>

            <span>
                {{
                    $venta
                        ->viaje
                        ->vehiculoChoferRuta
                        ->ruta
                        ->destino
                    ?? '-'
                }}
            </span>
        </div>

        <div class="row">
            <span class="label">
                Fecha/Hora salida:
            </span>

            <span>
                {{
                    $venta
                        ->viaje
                        ->vehiculoChoferRuta
                        ->hora_inicio
                        ?->format('d/m/Y H:i')
                    ?? '-'
                }}
            </span>
        </div>

        <div class="row">
            <span class="label">
                Vehículo:
            </span>

            <span>
                {{
                    $venta
                        ->viaje
                        ->vehiculoChoferRuta
                        ->asignacionVehiculoChofer
                        ->vehiculo
                        ->placa
                    ?? '-'
                }}
            </span>
        </div>

        <div class="row">
            <span class="label">
                Chofer:
            </span>

            <span>
                {{
                    trim(
                        (
                            $venta
                                ->viaje
                                ->vehiculoChoferRuta
                                ->asignacionVehiculoChofer
                                ->chofer
                                ->usuario
                                ->nombres
                            ?? ''
                        )
                        .
                        ' '
                        .
                        (
                            $venta
                                ->viaje
                                ->vehiculoChoferRuta
                                ->asignacionVehiculoChofer
                                ->chofer
                                ->usuario
                                ->primer_apellido
                            ?? ''
                        )
                    )
                }}
            </span>
        </div>

        <div class="row">
            <span class="label">
                Forma de pago:
            </span>

            <span>
                {{ $venta->forma_pago ?? '-' }}
            </span>
        </div>

        <div class="row total">
            <span class="label">
                Total:
            </span>

            <span>
                Bs {{ number_format($venta->precio_total, 2) }}
            </span>
        </div>

        <table>

            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Asiento
                    </th>

                    <th>
                        Pasajero
                    </th>

                    <th>
                        CI
                    </th>

                    <th>
                        Precio
                    </th>

                </tr>

            </thead>

            <tbody>

                @foreach(
                    $venta->detalles
                    as $detalle
                )

                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            {{
                                $detalle
                                    ->asiento
                                    ->numero_asiento
                                ??
                                $detalle
                                    ->asiento
                                    ->fila
                                .
                                '-'
                                .
                                $detalle
                                    ->asiento
                                    ->columna
                            }}
                        </td>

                        <td>
                            {{
                                trim(
                                    $detalle
                                        ->pasajero
                                        ->nombres
                                    .
                                    ' '
                                    .
                                    $detalle
                                        ->pasajero
                                        ->apellido_paterno
                                    .
                                    ' '
                                    .
                                    $detalle
                                        ->pasajero
                                        ->apellido_materno
                                )
                            }}
                        </td>

                        <td>
                            {{
                                $detalle
                                    ->pasajero
                                    ->ci
                                ?? '-'
                            }}
                        </td>

                        <td>
                            Bs
                            {{
                                number_format(
                                    $detalle
                                        ->precio_unitario,
                                    2
                                )
                            }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

        @if(
            isset($qrData)
        )

            <div class="qr">

                <img
                    src="{{ $qrData }}"
                    alt="QR"
                >

            </div>

        @endif

        <div class="footer">
            Gracias por su compra. Conserve este boleto.
        </div>

    </div>

</body>

</html>