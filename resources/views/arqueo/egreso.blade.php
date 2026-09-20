<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Egreso #{{ $egreso->id }}</title>
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
            border: 2px solid #a02020;
            padding: 14px;
            margin-bottom: 16px;
            background: #fdf3f3;
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
            color: #a02020;
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
        <h1>Comprobante de Egreso</h1>
        <p>CatuDrive · Movimiento de caja</p>
        <p>Generado el {{ $generadoEn->format('d/m/Y H:i') }} · Egreso #{{ $egreso->id }}</p>
    </div>

    <div class="monto-box">
        <span class="monto-label">Monto registrado</span>
        <span class="monto-value">Bs {{ number_format((float) $egreso->monto, 2) }}</span>
    </div>

    <table class="info">
        <tr>
            <td class="label">Fecha de registro</td>
            <td>{{ $egreso->fecha_registro?->format('d/m/Y H:i') ?? '-' }}</td>
            <td class="label">Tipo de pago</td>
            <td>{{ $egreso->tipo_pago }}</td>
        </tr>
        <tr>
            <td class="label">Tipo de transacción</td>
            <td colspan="3">
                {{ $egreso->tipoTransaccion?->transaccion ?? '-' }}
                @if($egreso->tipoTransaccion?->codigo)
                    <span style="color:#888;">({{ $egreso->tipoTransaccion->codigo }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Estado</td>
            <td>{{ $egreso->estado }}</td>
            <td class="label">Usuario</td>
            <td>
                {{ $egreso->user?->usuario ?? '-' }}
                @if($egreso->user?->nombres)
                    · {{ trim(($egreso->user->nombres ?? '') . ' ' . ($egreso->user->primer_apellido ?? '')) }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Arqueo</td>
            <td>#{{ $egreso->arqueo?->id ?? '-' }}</td>
            <td class="label">Apertura del arqueo</td>
            <td>{{ $egreso->arqueo?->fecha_apertura?->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Detalle</div>
    <div class="detalle">{{ $egreso->detalle }}</div>

    <div class="footer">
        CatuDrive · Comprobante interno de egreso · Documento generado automáticamente
    </div>
</body>

</html>