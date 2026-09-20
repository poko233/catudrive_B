<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Ingreso #{{ $ingreso->id }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 12mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #111;
        }

        .header {
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .header h1 {
            margin: 0 0 4px;
            font-size: 16px;
            text-transform: uppercase;
        }

        .header p {
            margin: 2px 0;
            color: #555;
            font-size: 9px;
        }

        .monto-box {
            text-align: center;
            border: 2px solid #1e7a3a;
            padding: 14px;
            margin-bottom: 16px;
            background: #f3fbf5;
        }

        .monto-label {
            display: block;
            font-size: 9px;
            color: #555;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .monto-value {
            font-size: 24px;
            font-weight: bold;
            color: #1e7a3a;
        }

        table.info {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        table.info td {
            padding: 6px 8px;
            border: 1px solid #cfcfcf;
            font-size: 10px;
            vertical-align: top;
        }

        table.info .label {
            background: #efefef;
            width: 22%;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            margin: 14px 0 4px;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            color: #444;
        }

        .detalle {
            border: 1px solid #cfcfcf;
            padding: 10px;
            min-height: 50px;
            font-size: 10px;
            white-space: pre-wrap;
        }

        .footer {
            margin-top: 20px;
            padding-top: 6px;
            border-top: 1px solid #ccc;
            color: #666;
            font-size: 8px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Comprobante de Ingreso</h1>
        <p>CatuDrive · Movimiento de caja</p>
        <p>Generado el {{ $generadoEn->format('d/m/Y H:i') }} · Ingreso #{{ $ingreso->id }}</p>
    </div>

    <div class="monto-box">
        <span class="monto-label">Monto registrado</span>
        <span class="monto-value">Bs {{ number_format((float) $ingreso->monto, 2) }}</span>
    </div>

    <table class="info">
        <tr>
            <td class="label">Fecha de registro</td>
            <td>{{ $ingreso->fecha_registro?->format('d/m/Y H:i') ?? '-' }}</td>
            <td class="label">Tipo de pago</td>
            <td>{{ $ingreso->tipo_pago }}</td>
        </tr>
        <tr>
            <td class="label">Tipo de transacción</td>
            <td colspan="3">
                {{ $ingreso->tipoTransaccion?->transaccion ?? '-' }}
                @if($ingreso->tipoTransaccion?->codigo)
                    <span style="color:#888;">({{ $ingreso->tipoTransaccion->codigo }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Estado</td>
            <td>{{ $ingreso->estado }}</td>
            <td class="label">Usuario</td>
            <td>
                {{ $ingreso->user?->usuario ?? '-' }}
                @if($ingreso->user?->nombres)
                    · {{ trim(($ingreso->user->nombres ?? '') . ' ' . ($ingreso->user->primer_apellido ?? '')) }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Arqueo</td>
            <td>#{{ $ingreso->arqueo?->id ?? '-' }}</td>
            <td class="label">Apertura del arqueo</td>
            <td>{{ $ingreso->arqueo?->fecha_apertura?->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Detalle</div>
    <div class="detalle">{{ $ingreso->detalle }}</div>

    <div class="footer">
        CatuDrive · Comprobante interno de ingreso · Documento generado automáticamente
    </div>
</body>

</html>