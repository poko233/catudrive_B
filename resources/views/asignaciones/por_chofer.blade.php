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
                <span class="summary-label">Asignaciones activas</span>
                <span class="summary-value">{{ $reporte['total_asignaciones'] }}</span>
            </td>
            <td>
                <span class="summary-label">Choferes asignados</span>
                <span class="summary-value">{{ $reporte['total_choferes'] }}</span>
            </td>
            <td colspan="2">
                <span class="summary-label">Periodo de inicio</span>
                <span class="summary-value">
                    @if(!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin']))
                        {{ \Carbon\Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y') }}
                        -
                        {{ \Carbon\Carbon::parse($filtros['fecha_fin'])->format('d/m/Y') }}
                    @else
                        Todas las fechas
                    @endif
                </span>
            </td>
        </tr>
    </table>

    @if(($reporte['items'] ?? collect())->count() > 0)
        @foreach($reporte['items'] as $grupo)
            <div class="group-title">
                {{ $grupo['nombre_chofer'] ?: 'Chofer sin nombre' }}
                · CI {{ $grupo['ci'] ?? '-' }}
                · Carnet sindical {{ $grupo['carnet_sindical'] ?? '-' }}
            </div>

            <table class="data">
                <thead>
                    <tr>
                        <th class="center">#</th>
                        <th>Placa</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Marca / Modelo</th>
                        <th>Color</th>
                        <th class="center">Capacidad</th>
                        <th>Fecha asignación</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo['asignaciones'] as $asignacion)
                        <tr>
                            <td class="center">{{ $loop->iteration }}</td>
                            <td>{{ $asignacion->vehiculo?->placa ?? '-' }}</td>
                            <td>{{ $asignacion->vehiculo?->categoria?->categoria ?? '-' }}</td>
                            <td>{{ $asignacion->vehiculo?->tipo ?? '-' }}</td>
                            <td>
                                {{ trim(($asignacion->vehiculo?->marca ?? '') . ' ' . ($asignacion->vehiculo?->modelo ?? '')) ?: '-' }}
                            </td>
                            <td>{{ $asignacion->vehiculo?->color ?? '-' }}</td>
                            <td class="center">{{ $asignacion->vehiculo?->capacidad ?? '-' }}</td>
                            <td>{{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $asignacion->observacion ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <div class="empty">No existen vehículos asignados para los filtros seleccionados.</div>
    @endif

    <div class="footer">Generado por CatuDrive.</div>
</body>
</html>
