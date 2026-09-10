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
            vertical-align: top;
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

        .section-title {
            margin: 14px 0 6px;
            font-size: 12px;
            font-weight: bold;
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
        <p>Reporte de encomiendas - CatuDrive</p>
        <p>
            Fecha del reporte:
            {{ $generadoEn->format('d/m/Y H:i') }}
        </p>
    </div>

    <table class="summary" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <span class="summary-label">Registros</span>
                <span class="summary-value">{{ $reporte['total_registros'] }}</span>
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
                <span class="summary-label">Ruta</span>
                <span class="summary-value">
                    @if($rutaFiltro)
                        {{ $rutaFiltro->origen }} - {{ $rutaFiltro->destino }}
                    @else
                        Todas las rutas
                    @endif
                </span>
            </td>
            <td>
                @if(isset($reporte['total_ingresos']))
                    <span class="summary-label">Total ingresos</span>
                    <span class="summary-value">Bs {{ number_format((float) $reporte['total_ingresos'], 2) }}</span>
                @elseif(isset($reporte['total_destinos']))
                    <span class="summary-label">Destinos</span>
                    <span class="summary-value">{{ $reporte['total_destinos'] }}</span>
                @else
                    <span class="summary-label">Tipo</span>
                    <span class="summary-value">{{ ucfirst(str_replace('_', ' ', $reporte['tipo'])) }}</span>
                @endif
            </td>
        </tr>
    </table>

    @if(($reporte['items'] ?? collect())->count() > 0)
        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Guía</th>
                    <th>Fecha</th>
                    <th>Ruta</th>
                    <th>Remitente</th>
                    <th>Destinatario</th>
                    <th>Descripción</th>
                    <th>Cant.</th>
                    <th>Estado</th>
                    <th>Viaje</th>
                    <th>Chofer / Vehículo</th>
                    <th class="right">Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['items'] as $item)
                    @php
                        $viaje = $item->viajeEncomienda?->viaje;
                        $vehiculoChoferRuta = $viaje?->vehiculoChoferRuta;
                        $asignacion = $vehiculoChoferRuta?->asignacion;
                        $usuario = $asignacion?->chofer?->usuario;
                        $vehiculo = $asignacion?->vehiculo;
                        $nombreChofer = trim(implode(' ', array_filter([
                            $usuario?->nombres,
                            $usuario?->primer_apellido,
                            $usuario?->segundo_apellido,
                        ])));
                        $nombreVehiculo = trim(implode(' ', array_filter([
                            $vehiculo?->placa,
                            $vehiculo?->marca,
                            $vehiculo?->modelo,
                        ])));
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $item->guia }}</td>
                        <td>{{ $item->created_at?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            {{ $item->ruta?->origen ?? '-' }}
                            -
                            {{ $item->ruta?->destino ?? '-' }}
                        </td>
                        <td>{{ $item->remitente }}</td>
                        <td>{{ $item->destinatario }}</td>
                        <td>{{ $item->descripcion ?: '-' }}</td>
                        <td class="center">{{ $item->cantidad }}</td>
                        <td>{{ $item->estado }}</td>
                        <td>
                            @if($viaje)
                                #{{ $viaje->id }}
                                @if($vehiculoChoferRuta?->hora_inicio)
                                    <br>{{ $vehiculoChoferRuta->hora_inicio->format('d/m/Y H:i') }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            {{ $nombreChofer ?: '-' }}
                            @if($nombreVehiculo)
                                <br>{{ $nombreVehiculo }}
                            @endif
                        </td>
                        <td class="right">Bs {{ number_format((float) $item->precio, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">
            No existen encomiendas para los filtros seleccionados.
        </div>
    @endif

    @if(($reporte['tipo'] ?? '') === 'por_destino' && !empty($reporte['resumen_destinos']))
        <div class="section-title">Resumen por destino</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Destino</th>
                    <th class="center">Cantidad</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['resumen_destinos'] as $resumen)
                    <tr>
                        <td>{{ $resumen['destino'] }}</td>
                        <td class="center">{{ $resumen['cantidad'] }}</td>
                        <td class="right">Bs {{ number_format((float) $resumen['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generado por CatuDrive.
    </div>
</body>
</html>
