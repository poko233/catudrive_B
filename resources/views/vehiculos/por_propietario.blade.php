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
            margin: 14px 0 6px;
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
        <p>Reporte de vehículos - CatuDrive</p>
        <p>Fecha del reporte: {{ $generadoEn->format('d/m/Y H:i') }}</p>
    </div>

    <table class="summary" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <span class="summary-label">Propietarios</span>
                <span class="summary-value">{{ $reporte['total_propietarios'] }}</span>
            </td>
            <td>
                <span class="summary-label">Fechas</span>
                <span class="summary-value">
                    @if(!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin']))
                        {{ \Carbon\Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y') }}
                        -
                        {{ \Carbon\Carbon::parse($filtros['fecha_fin'])->format('d/m/Y') }}
                    @else
                        Todas
                    @endif
                </span>
            </td>
            <td>
                <span class="summary-label">Vehículos vinculados</span>
                <span class="summary-value">{{ $reporte['total_registros'] }}</span>
            </td>
            <td>
                <span class="summary-label">Tipo</span>
                <span class="summary-value">Por propietario</span>
            </td>
        </tr>
    </table>

    @if(($reporte['items'] ?? collect())->count() > 0)
        @foreach($reporte['items'] as $grupo)
            <div class="section-title">
                {{ $grupo['nombre_completo'] ?: 'Chofer #' . $grupo['id_chofer'] }}
                @if($grupo['ci']) · CI: {{ $grupo['ci'] }} @endif
                @if($grupo['carnet_sindical']) · CS: {{ $grupo['carnet_sindical'] }} @endif
                ({{ $grupo['total_vehiculos'] }} vehículo(s))
            </div>
            <table class="data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Placa</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th class="center">Capacidad</th>
                        <th>Estado</th>
                        <th>Registro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo['vehiculos'] as $vehiculo)
                        <tr>
                            <td class="center">{{ $loop->iteration }}</td>
                            <td>{{ $vehiculo->placa }}</td>
                            <td>{{ $vehiculo->categoria?->categoria ?? '-' }}</td>
                            <td>{{ $vehiculo->tipo }}</td>
                            <td>{{ $vehiculo->marca }}</td>
                            <td>{{ $vehiculo->modelo }}</td>
                            <td class="center">{{ $vehiculo->capacidad }}</td>
                            <td>{{ $vehiculo->estado }}</td>
                            <td>{{ $vehiculo->created_at?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <div class="empty">No existen propietarios para los filtros seleccionados.</div>
    @endif

    <div class="footer">Generado por CatuDrive.</div>
</body>

</html>