<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ $reporte['titulo'] }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 12mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #111;
        }

        .header {
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .header p {
            margin: 2px 0;
            color: #555;
        }

        .summary {
            width: 100%;
            margin-bottom: 10px;
        }

        .summary td {
            width: 25%;
            border: 1px solid #d2d2d2;
            padding: 7px;
            vertical-align: top;
        }

        .summary-label {
            display: block;
            color: #666;
            font-size: 8px;
            margin-bottom: 2px;
        }

        .summary-value {
            font-size: 11px;
            font-weight: bold;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th,
        table.data td {
            border: 1px solid #cfcfcf;
            padding: 4px;
        }

        table.data th {
            background: #efefef;
            font-weight: bold;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .empty {
            padding: 18px;
            text-align: center;
            border: 1px solid #d2d2d2;
            color: #666;
        }

        .footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #ccc;
            color: #666;
            font-size: 8px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $reporte['titulo'] }}</h1>
        <p>CatuDrive - Reportes de pasajes</p>
        <p>Fecha del reporte: {{ $generadoEn->format('d/m/Y H:i') }}</p>
    </div>

    <table class="summary" cellspacing="0" cellpadding="0">
        <tr>
            <td><span class="summary-label">Días con ventas</span><span
                    class="summary-value">{{ $reporte['total_registros'] }}</span></td>
            <td><span class="summary-label">Ventas totales</span><span
                    class="summary-value">{{ $reporte['total_ventas'] }}</span></td>
            <td><span class="summary-label">Asientos vendidos</span><span
                    class="summary-value">{{ $reporte['total_asientos'] }}</span></td>
            <td><span class="summary-label">Ingreso total</span><span class="summary-value">Bs
                    {{ number_format($reporte['ingreso_total'], 2) }}</span></td>
        </tr>
    </table>

    @if(($reporte['items'] ?? collect())->count() > 0)
        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th class="center">N.º ventas</th>
                    <th class="center">Asientos</th>
                    <th class="right">Ingreso (Bs)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['items'] as $item)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }}</td>
                        <td class="center">{{ $item->total_ventas }}</td>
                        <td class="center">{{ $item->total_asientos }}</td>
                        <td class="right">{{ number_format((float) $item->ingreso_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No existen ventas de pasajes en el rango seleccionado.</div>
    @endif

    <div class="footer">Generado por CatuDrive.</div>
</body>

</html>