@php
    $viaje = $reporte['viaje'];
    $ruta = $viaje->vehiculoChoferRuta?->ruta;
    $vehiculo = $viaje->vehiculoChoferRuta?->asignacion?->vehiculo;
    $chofer = $viaje->vehiculoChoferRuta?->asignacion?->chofer?->usuario;
    $nombreChofer = $chofer
        ? trim(implode(' ', array_filter([
            $chofer->nombres,
            $chofer->primer_apellido !== '-' ? $chofer->primer_apellido : '',
            $chofer->segundo_apellido,
        ])))
        : '-';
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Planilla de pasajeros - Viaje #{{ $viaje->id }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 14mm;
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
            margin-bottom: 12px;
            text-align: center;
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

        .info {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .info td {
            padding: 5px 7px;
            border: 1px solid #cfcfcf;
            font-size: 10px;
            vertical-align: top;
        }

        .info .label {
            background: #efefef;
            width: 22%;
            font-weight: bold;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th,
        table.data td {
            border: 1px solid #cfcfcf;
            padding: 4px 6px;
            font-size: 10px;
        }

        table.data th {
            background: #efefef;
            font-weight: bold;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .summary {
            width: 100%;
            margin-top: 12px;
            border-collapse: collapse;
        }

        .summary td {
            padding: 5px 7px;
            border: 1px solid #cfcfcf;
            font-size: 10px;
        }

        .summary .label {
            background: #efefef;
            font-weight: bold;
        }

        .signatures {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }

        .signatures td {
            text-align: center;
            padding-top: 40px;
            font-size: 9px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 4px;
            display: inline-block;
            min-width: 200px;
        }

        .empty {
            padding: 18px;
            text-align: center;
            border: 1px solid #d2d2d2;
            color: #666;
            font-size: 10px;
        }

        .footer {
            margin-top: 12px;
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
        <h1>Planilla de Pasajeros para Tránsito</h1>
        <p>CatuDrive - Documento oficial de control de pasajeros</p>
        <p>Generado el {{ $generadoEn->format('d/m/Y H:i') }} · Viaje #{{ $viaje->id }}</p>
    </div>

    <table class="info">
        <tr>
            <td class="label">Origen / Destino</td>
            <td colspan="3">
                {{ $ruta?->origen ?? '-' }} → {{ $ruta?->destino ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">Fecha de salida</td>
            <td>{{ $ruta?->fecha_inicio ? \Carbon\Carbon::parse($ruta->fecha_inicio)->format('d/m/Y') : '-' }}</td>
            <td class="label">Hora de salida</td>
            <td>{{ $ruta?->hora_inicio ? substr($ruta->hora_inicio, 0, 5) : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Vehículo / Placa</td>
            <td>
                {{ $vehiculo?->tipo ?? '-' }} {{ $vehiculo?->marca ?? '' }}
                @if($vehiculo?->placa)
                    · {{ $vehiculo->placa }}
                @endif
            </td>
            <td class="label">Capacidad</td>
            <td>{{ $reporte['total_asientos'] }} asientos</td>
        </tr>
        <tr>
            <td class="label">Chofer</td>
            <td colspan="3">{{ $nombreChofer }}</td>
        </tr>
    </table>

    @if(($reporte['items'] ?? collect())->count() > 0)
        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width: 6%;">N.º</th>
                    <th style="width: 12%;">Asiento</th>
                    <th>Nombre completo</th>
                    <th style="width: 20%;">C.I.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['items'] as $detalle)
                    @php
                        $pasajero = $detalle->pasajero;
                        $nombreCompleto = $pasajero
                            ? trim(implode(' ', array_filter([
                                $pasajero->nombres,
                                $pasajero->apellido_paterno,
                                $pasajero->apellido_materno,
                            ])))
                            : '(Sin pasajero)';
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="center">
                            {{ $detalle->asiento?->numero_asiento
                    ?? (($detalle->asiento?->fila ?? '-') . '-' . ($detalle->asiento?->columna ?? '-')) }}
                        </td>
                        <td>{{ $nombreCompleto }}</td>
                        <td>{{ $pasajero?->ci ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td class="label">Total de pasajeros</td>
                <td>{{ $reporte['total_pasajeros'] }}</td>
                <td class="label">Espacios disponibles</td>
                <td>{{ $reporte['disponibles'] }}</td>
            </tr>
        </table>
    @else
        <div class="empty">Este viaje no tiene pasajeros registrados.</div>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <span class="signature-line">Firma del chofer</span>
                <div>{{ $nombreChofer }}</div>
            </td>
            <td>
                <span class="signature-line">Firma del responsable</span>
                <div>Control de tránsito</div>
            </td>
        </tr>
    </table>

    <div class="footer">CatuDrive · Planilla oficial de pasajeros · Viaje #{{ $viaje->id }}</div>
</body>

</html>