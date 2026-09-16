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

        .section-title {
            margin: 12px 0 6px;
            font-size: 12px;
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
            vertical-align: top;
        }

        table.data th {
            background: #efefef;
            font-weight: bold;
            text-align: left;
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
        <p>Reporte de choferes - CatuDrive</p>
        <p>Fecha del reporte: {{ $generadoEn->format('d/m/Y H:i') }}</p>
    </div>

    <table class="summary" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <span class="summary-label">Registros</span>
                <span class="summary-value">{{ $reporte['total_registros'] }}</span>
            </td>
            <td>
                <span class="summary-label">Activos</span>
                <span class="summary-value">{{ $reporte['resumen_estado']['Activo'] ?? 0 }}</span>
            </td>
            <td>
                <span class="summary-label">Inactivos</span>
                <span class="summary-value">{{ $reporte['resumen_estado']['Inactivo'] ?? 0 }}</span>
            </td>
            <td>
                <span class="summary-label">Estado</span>
                <span class="summary-value">{{ $filtros['estado'] ?? 'Todos' }}</span>
            </td>
        </tr>
    </table>

    @if(($reporte['items'] ?? collect())->count() > 0)
        <table class="data">
            <thead>
                <tr>
                    <th class="center">#</th>
                    <th>Chofer</th>
                    <th>CI</th>
                    <th>Teléfono</th>
                    <th>Carnet sindical</th>
                    <th>Nro. licencia</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Registro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['items'] as $item)
                    @php
                        $usuario = $item->usuario;
                        $nombre = trim(implode(' ', array_filter([
                            $usuario?->nombres,
                            $usuario?->primer_apellido !== '-' ? $usuario?->primer_apellido : '',
                            $usuario?->segundo_apellido,
                        ])));
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $nombre ?: '-' }}</td>
                        <td>{{ $usuario?->ci ?? '-' }}</td>
                        <td>{{ $usuario?->celular ?? '-' }}</td>
                        <td>{{ $item->carnet_sindical ?: '-' }}</td>
                        <td>{{ $item->numero_licencia ?: '-' }}</td>
                        <td>{{ $item->categoria_licencia ?: '-' }}</td>
                        <td>{{ $usuario?->estado ?? '-' }}</td>
                        <td>{{ $item->created_at?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No existen choferes para los filtros seleccionados.</div>
    @endif

    <div class="footer">Generado por CatuDrive.</div>
</body>
</html>
