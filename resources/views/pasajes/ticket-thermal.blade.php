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
            font-family:
                'DejaVu Sans Mono',
                'Courier New',
                monospace;

            font-size: 12px;
            width: 48mm;
            margin: 0 auto;
            padding: 0;

            color: #000;
            background: #fff;

            font-weight: 900;
            line-height: 1.3;

            -webkit-font-smoothing: none;
            text-rendering: geometricPrecision;
        }

        .ticket {
            width: 100%;
            padding: 1mm 0.5mm;
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
    | TÍTULOS — SIGUEN GRANDES
    |--------------------------------------------------------------------------
    */

        .title {
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .subtitle {
            font-size: 11px;
            font-weight: 900;
        }

        .line {
            width: 100%;
            border-top: 1px dashed #000;
            margin: 3px 0;
        }

        /*
    |--------------------------------------------------------------------------
    | FILA DATO : VALOR
    |--------------------------------------------------------------------------
    |
    | ANTES: 12px / gap 2px
    | AHORA: 11px / gap 4px
    |
    | El .value tiene flex:1 para aprovechar todo el espacio
    | restante y quedar right-aligned sin pegarse al label.
    |
    */

        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 4px;
            margin-bottom: 0;
            font-weight: 900;
            font-size: 11px;
        }

        .row .label {
            flex-shrink: 0;
            font-weight: 900;
        }

        .row .value {
            flex: 1;
            text-align: right;
            white-space: nowrap;
            font-weight: 900;
        }

        /*
    |--------------------------------------------------------------------------
    | ITEMS DEL DETALLE
    |--------------------------------------------------------------------------
    |
    | SIGUE EN 12px (donde sí cabe bien)
    |
    */

        .item {
            margin-bottom: 4px;
            font-weight: 900;
            font-size: 12px;
        }

        .item-header {
            margin-bottom: 1px;
            font-weight: 900;
        }

        .item-detail {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2px;
            font-weight: 900;
        }

        .item-detail span:last-child {
            text-align: right;
            white-space: nowrap;
        }

        /*
    |--------------------------------------------------------------------------
    | TOTALES
    |--------------------------------------------------------------------------
    */

        .totales {
            text-align: right;
            font-weight: 900;
            margin-top: 4px;
            font-size: 12px;
        }

        .totales div {
            margin-bottom: 1px;
            font-weight: 900;
        }

        .totales .total-monto {
            /*
        |--------------------------------------------------------------------------
        | ANTES: 15px  ->  AHORA: 13px
        |--------------------------------------------------------------------------
        |
        | A 15px "TOTAL MONTO Bs. 50.00" no cabía en una línea y
        | el monto saltaba abajo. A 13px cabe sin wrap.
        |
        */

            font-size: 13px;
            margin-top: 3px;
            font-weight: 900;
            white-space: nowrap;
        }

        .son {
            margin-top: 4px;
            font-weight: 900;
            font-size: 12px;
        }

        .centavos {
            margin-top: 1px;
            font-weight: 900;
            font-size: 12px;
        }

        .centavos .underline {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 6px;
            font-size: 11px;
            font-weight: 900;
        }
    </style>
</head>

<body>

    <div class="ticket">

        {{-- ================================================================
        | CABECERA — EMPRESA
        ================================================================ --}}

        @if($empresa)
            <div class="center title">
                {{ $empresa->empresa }}
            </div>

            @if($empresa->direccion)
                <div class="center subtitle">
                    {{ $empresa->direccion }}
                </div>
            @endif

            @if($empresa->telefono)
                <div class="center subtitle">
                    Teléfono: {{ $empresa->telefono }}
                </div>
            @endif
        @endif

        @php
            $vcr = $venta->viaje?->vehiculoChoferRuta;
            $ruta = $vcr?->ruta;
            $vehiculo = $vcr?->asignacion?->vehiculo;
            $chofer = $vcr?->asignacion?->chofer?->usuario;

            $placaTexto = $vehiculo?->placa ?? '-';
            $colorTexto = $vehiculo?->color ?: '-';

            $choferTexto = $chofer
                ? trim(implode(' ', array_filter([
                    $chofer->nombres,
                    $chofer->primer_apellido,
                ])))
                : '-';

            /*
            |--------------------------------------------------------------------------
            | USUARIO VENDEDOR — usuario (login) + nombre completo
            |--------------------------------------------------------------------------
            */

            $user = $venta->user;

            $usuarioLogin = $user?->usuario ?? '-';

            $usuarioNombre = $user
                ? trim(implode(' ', array_filter([
                    $user->nombres,
                    $user->primer_apellido,
                    $user->segundo_apellido,
                ])))
                : '-';

            if ($usuarioNombre === '') {
                $usuarioNombre = '-';
            }
        @endphp

        @if($ruta?->origen)
            <div class="center subtitle">
                {{ $ruta->origen }}
            </div>
        @endif

        <div class="center title" style="margin-top: 6px;">
            BOLETO DE VIAJE
        </div>

        <div class="line"></div>

        {{-- ================================================================
        | DATOS GENERALES
        ================================================================ --}}

        <div class="row">
            <span class="label">Fecha:</span>
            <span class="value">{{ now()->format('d/m/Y H:i') }}</span>
        </div>

        <div class="row">
            <span class="label">Boleto:</span>
            <span class="value">{{ $venta->id }}</span>
        </div>

        <div class="row">
            <span class="label">Origen:</span>
            <span class="value">{{ $ruta?->origen ?? '-' }}</span>
        </div>

        <div class="row">
            <span class="label">Destino:</span>
            <span class="value">{{ $ruta?->destino ?? '-' }}</span>
        </div>

        <div class="row">
            <span class="label">Salida:</span>
            <span class="value">
                {{ $vcr?->hora_inicio?->format('d/m/Y H:i') ?? '-' }}
            </span>
        </div>

        <div class="row">
            <span class="label">PLACA:</span>
            <span class="value">{{ $placaTexto }}</span>
        </div>

        <div class="row">
            <span class="label">COLOR:</span>
            <span class="value">{{ $colorTexto }}</span>
        </div>

        <div class="row">
            <span class="label">Chofer:</span>
            <span class="value">{{ $choferTexto }}</span>
        </div>

        <div class="row">
            <span class="label">T. Pago:</span>
            <span class="value">{{ $venta->forma_pago ?? '-' }}</span>
        </div>

        <div class="line"></div>
        <div class="center bold">DETALLE</div>
        <div class="line"></div>

        {{-- ================================================================
        | DETALLE
        ================================================================ --}}

        @php
            $totalProductos = 0;
            $subtotal = 0.0;
        @endphp

        @foreach($venta->detalles as $detalle)

            @php
                $piso = $detalle->asiento?->piso;

                $numeroAsiento =
                    $detalle->asiento?->numero_asiento
                    ?? (
                        ($detalle->asiento?->fila ?? '-')
                        . '-' .
                        ($detalle->asiento?->columna ?? '-')
                    );

                $codigoAsiento = $piso
                    ? 'P' . $piso->numero . '-' . $numeroAsiento
                    : $numeroAsiento;

                /*
                |--------------------------------------------------------------------------
                | PASAJERO — APELLIDO PATERNO + NOMBRES + CI
                |--------------------------------------------------------------------------
                */

                $pasajero = $detalle->pasajero;

                if ($pasajero) {
                    $apellido = strtoupper(
                        (string) $pasajero->apellido_paterno
                    );

                    $nombres = strtoupper(
                        trim((string) $pasajero->nombres)
                    );

                    $ciPasajero = $pasajero->ci
                        ? ' (' . $pasajero->ci . ')'
                        : '';

                    $nombrePasajero = trim(
                        $apellido . ' ' . $nombres . $ciPasajero
                    );
                } else {
                    $nombrePasajero = '(SIN PASAJERO)';
                }

                $precio = (float) $detalle->precio_unitario;

                $totalProductos += 1;
                $subtotal += $precio;
            @endphp

            <div class="item">
                <div class="item-header">
                    {{ $codigoAsiento }} | {{ $nombrePasajero }}
                </div>

                <div class="item-detail">
                    <span>1 x Bs. {{ number_format($precio, 2) }}</span>
                    <span>Bs. {{ number_format($precio, 2) }}</span>
                </div>
            </div>

        @endforeach

        <div class="line"></div>

        {{-- ================================================================
        | TOTALES — SIN DESCUENTO
        ================================================================ --}}

        @php
            $totalMonto = (float) $venta->precio_total;
            $centavos = (int) round(($totalMonto - floor($totalMonto)) * 100);
            $montoEnLetras = \App\Shared\Helpers\NumeroALetras::convertir($totalMonto);
        @endphp

        <div class="totales">
            <div>TOTAL PRODUCTOS {{ $totalProductos }}</div>
            <div>SUBTOTAL Bs. {{ number_format($subtotal, 2) }}</div>
            <div class="total-monto">
                TOTAL MONTO Bs. {{ number_format($totalMonto, 2) }}
            </div>
        </div>

        <div class="son">
            Son: {{ $montoEnLetras }}
        </div>

        <div class="centavos">
            <span class="underline">
                {{ str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) }}
            </span>/100 Bolivianos
        </div>

        <div class="line"></div>

        {{-- ================================================================
        | USUARIO Y FOOTER
        ================================================================ --}}

        <div class="row">
            <span class="label">USUARIO:</span>
            <span class="value">{{ $usuarioLogin }}</span>
        </div>

        <div class="row">
            <span class="label">NOMBRE:</span>
            <span class="value">{{ $usuarioNombre }}</span>
        </div>

        <div class="footer">
            Gracias por su compra
        </div>

    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>

</body>

</html>