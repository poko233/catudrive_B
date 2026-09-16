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

        .group-title {
            margin: 12px 0 6px;
            padding: 6px 8px;
            background: #efefef;
            border: 1px solid #d2d2d2;
            font-weight: bold;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
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
        <p>Reporte de asignaciones - CatuDrive</p>
        <p>Fecha del reporte: {{ $generadoEn->format('d/m/Y H:i') }}</p>
    </div>

    <table class="summary" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <span class="summary-label">Sin asignación activa</span>
                <span class="summary-value">{{ $reporte['total_registros'] }}</span>
            </td>
            <td>
                <span class="summary-label">Operativos</span>
                <span class="summary-value">{{ $reporte['resumen_estado']['Operativo'] ?? 0 }}</span>
            </td>
            <td>
                <span class="summary-label">En mantenimiento</span>
                <span class="summary-value">{{ $reporte['resumen_estado']['En mantenimiento'] ?? 0 }}</span>
            </td>
            <td>
                <span class="summary-label">Baja</span>
                <span class="summary-value">{{ $reporte['resumen_estado']['Baja'] ?? 0 }}</span>
            </td>
        </tr>
    </table>

    @if(($reporte['items'] ?? collect())->count() > 0)
        <table class="data">
            <thead>
                <tr>
                    <th class="center">#</th>
                    <th>Placa</th>
                    <th>Categoría</th>
                    <th>Tipo</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Color</th>
                    <th class="center">Capacidad</th>
                    <th>Estado</th>
                    <th>Registro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['items'] as $item)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $item->placa }}</td>
                        <td>{{ $item->categoria?->categoria ?? '-' }}</td>
                        <td>{{ $item->tipo }}</td>
                        <td>{{ $item->marca }}</td>
                        <td>{{ $item->modelo }}</td>
                        <td>{{ $item->color ?: '-' }}</td>
                        <td class="center">{{ $item->capacidad }}</td>
                        <td>{{ $item->estado }}</td>
                        <td>{{ $item->created_at?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No existen vehículos sin asignación para los filtros seleccionados.</div>
    @endif

    <div class="footer">Generado por CatuDrive.</div>
</body>
</html>
