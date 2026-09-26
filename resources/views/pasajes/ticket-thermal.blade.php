<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <title>
        Ticket #{{ $venta->id }}
    </title>

    <style>
        /*
        |--------------------------------------------------------------------------
        | PAPEL
        |--------------------------------------------------------------------------
        |
        | Papel físico: 58 mm
        | Área útil:    48 mm
        |
        */

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

        /*
        |--------------------------------------------------------------------------
        | CUERPO
        |--------------------------------------------------------------------------
        |
        | Aumentado: 11px -> 13px
        |
        */

        body {
            font-family:
                'DejaVu Sans Mono',
                'Courier New',
                monospace;

            font-size: 13px;

            /* NO CAMBIAR: ancho fijo de 48mm */
            width: 48mm;

            margin: 0 auto;
            padding: 0;

            color: #000;
            background: #fff;

            font-weight: 900;

            line-height: 1.28;

            position: relative;

            /* NO CAMBIAR: compensa impresión corrida a la derecha */
            left: -1.25mm;

            -webkit-font-smoothing: none;
            text-rendering: geometricPrecision;
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

        /*
        |--------------------------------------------------------------------------
        | LÍNEAS
        |--------------------------------------------------------------------------
        */

        .line {
            width: 100%;
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        /*
        |--------------------------------------------------------------------------
        | FILAS
        |--------------------------------------------------------------------------
        */

        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 4px;
            margin-bottom: 2px;
            font-weight: 900;
        }

        .row span:first-child {
            flex-shrink: 0;
        }

        .row span:last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLA
        |--------------------------------------------------------------------------
        |
        | th/td aumentado: 9px -> 12px
        |
        */

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 4px;
            font-weight: 900;
        }

        th,
        td {
            font-size: 12px;
            text-align: left;
            vertical-align: top;
            padding: 2px 1px 2px 0;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        th {
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        /*
        |--------------------------------------------------------------------------
        | ANCHOS DE COLUMNAS
        |--------------------------------------------------------------------------
        |
        | NO CAMBIAR: los porcentajes deben sumar 100%
        |
        */

        th:nth-child(1),
        td:nth-child(1) {
            width: 22%;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 30%;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 20%;
        }

        th:nth-child(4),
        td:nth-child(4) {
            width: 28%;
            text-align: right;
        }

        /*
        |--------------------------------------------------------------------------
        | QR
        |--------------------------------------------------------------------------
        */

        .qr {
            text-align: center;
            margin: 7px 0 4px 0;
        }

        .qr img {
            width: 120px;
            height: 120px;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        |
        | Aumentado: 9px -> 11px
        |
        */

        .footer {
            text-align: center;
            margin-top: 4px;
            font-size: 11px;
            font-weight: 900;
        }

        /*
        |--------------------------------------------------------------------------
        | TÍTULO
        |--------------------------------------------------------------------------
        |
        | Aumentado: 15px -> 18px
        |
        */

        .brand {
            font-size: 18px;
            font-weight: 900;
            line-height: 1.1;
        }

        /*
        |--------------------------------------------------------------------------
        | RECEIPT NUMBER
        |--------------------------------------------------------------------------
        |
        | Aumentado: 10px -> 12px
        |
        */

        .receipt-number {
            font-size: 12px;
            margin-top: 2px;
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        |
        | Aumentado: 13px -> 16px
        |
        */

        .total-row {
            font-size: 16px;
            margin-top: 4px;
            margin-bottom: 3px;
        }
    </style>
</head>

<body>

    <div class="ticket">

        {{-- ================================================================
        | CABECERA
        ================================================================ --}}

        <div class="center bold brand">
            CatuDrive
        </div>

        <div class="center receipt-number">
            Comprobante #{{ $venta->id }}
        </div>

        <div class="line"></div>

        {{-- ================================================================
        | DATOS DE LA VENTA
        ================================================================ --}}

        <div class="row">
            <span class="bold">
                Estado:
            </span>

            <span>
                {{ $venta->estado }}
            </span>
        </div>

        <div class="row">
            <span class="bold">
                Fecha:
            </span>

            <span>
                {{ now()->format('d/m/Y H:i') }}
            </span>
        </div>

        <div class="row">
            <span class="bold">
                Origen:
            </span>

            <span>
                {{ $venta->viaje->vehiculoChoferRuta->ruta->origen ?? '-' }}
            </span>
        </div>

        <div class="row">
            <span class="bold">
                Destino:
            </span>

            <span>
                {{ $venta->viaje->vehiculoChoferRuta->ruta->destino ?? '-' }}
            </span>
        </div>

        {{-- ================================================================
        | FECHAS DEL VIAJE
        ================================================================ --}}

        @php
            $ruta =
                $venta
                    ->viaje
                    ->vehiculoChoferRuta
                    ->ruta
                ?? null;

            $fechaSalida =
                $ruta?->fecha_inicio
                ? \Carbon\Carbon::parse(
                    $ruta->fecha_inicio
                )->format('d/m/Y')
                : null;

            $horaSalida =
                $ruta?->hora_inicio
                ? substr(
                    $ruta->hora_inicio,
                    0,
                    5
                )
                : null;

            $fechaLlegada =
                $ruta?->fecha_fin
                ? \Carbon\Carbon::parse(
                    $ruta->fecha_fin
                )->format('d/m/Y')
                : null;

            $horaLlegada =
                $ruta?->hora_fin
                ? substr(
                    $ruta->hora_fin,
                    0,
                    5
                )
                : null;
        @endphp

        <div class="row">
            <span class="bold">
                Salida:
            </span>

            <span>
                {{
    trim(
        ($fechaSalida ?? '-') .
        ' ' .
        ($horaSalida ?? '')
    )
                }}
            </span>
        </div>

        <div class="row">
            <span class="bold">
                Llegada:
            </span>

            <span>
                {{
    trim(
        ($fechaLlegada ?? '-') .
        ' ' .
        ($horaLlegada ?? '')
    )
                }}
            </span>
        </div>

        {{-- ================================================================
        | VEHÍCULO
        ================================================================ --}}

        <div class="row">
            <span class="bold">
                Vehículo:
            </span>

            <span>
                {{
    $venta
        ->viaje
        ->vehiculoChoferRuta
        ->asignacion
        ->vehiculo
        ->placa
    ?? '-'
                }}
            </span>
        </div>

        {{-- ================================================================
        | CHOFER
        ================================================================ --}}

        <div class="row">
            <span class="bold">
                Chofer:
            </span>

            <span>
                {{
    trim(
        (
            $venta
                ->viaje
                ->vehiculoChoferRuta
                ->asignacion
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
                ->asignacion
                ->chofer
                ->usuario
                ->primer_apellido
            ?? ''
        )
    )
    ?: '-'
                }}
            </span>
        </div>

        <div class="line"></div>

        {{-- ================================================================
        | PASAJEROS
        ================================================================ --}}

        <table>

            <thead>
                <tr>
                    <th>
                        Piso/As.
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

                            @php
                                $piso =
                                    $detalle
                                        ->asiento
                                            ?->piso;

                                $numeroAsiento =
                                    $detalle
                                        ->asiento
                                        ->numero_asiento
                                    ??
                                    (
                                        (
                                            $detalle
                                                ->asiento
                                                ->fila
                                            ?? '-'
                                        )
                                        .
                                        '-'
                                        .
                                        (
                                            $detalle
                                                ->asiento
                                                ->columna
                                            ?? '-'
                                        )
                                    );

                                $pisoAsiento =
                                    $piso
                                    ? 'P' .
                                    $piso->numero .
                                    '-' .
                                    $numeroAsiento
                                    : $numeroAsiento;
                            @endphp

                            <tr>

                                <td>
                                    {{ $pisoAsiento }}
                                </td>

                                <td>

                                    @if(
                                                        $detalle->pasajero
                                                    )

                                                    {{
                                        strtoupper(
                                            $detalle
                                                ->pasajero
                                                ->apellido_paterno
                                        )
                                                            }}

                                                    {{
                                        strtoupper(
                                            mb_substr(
                                                $detalle
                                                    ->pasajero
                                                    ->nombres,
                                                0,
                                                1
                                            )
                                        )
                                                            }}.

                                    @else

                                        -

                                    @endif

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

        <div class="line"></div>

        {{-- ================================================================
        | TOTAL
        ================================================================ --}}

        <div class="row bold total-row">

            <span>
                TOTAL
            </span>

            <span>
                Bs
                {{
    number_format(
        $venta->precio_total,
        2
    )
                }}
            </span>

        </div>

        {{-- ================================================================
        | QR
        ================================================================ --}}

        @if(
                isset($qrData)
            )

            <div class="qr">

                <img src="{{ $qrData }}" alt="QR">

            </div>

        @endif

        {{-- ================================================================
        | FOOTER
        ================================================================ --}}

        <div class="footer">
            Gracias por su compra
        </div>

    </div>

    {{-- ================================================================
    | IMPRESIÓN WEB
    ================================================================ --}}

    <script>
        window.onload =
            function () {
                window.print();
            };
    </script>

</body>

</html>