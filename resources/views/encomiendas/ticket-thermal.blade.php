<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $tipo === 'comprobante' ? 'Detalle de encomienda' : 'Etiqueta de encomienda' }}</title>
<style>
@page{size:48mm auto;margin:0}
html,body{margin:0;padding:0}*{box-sizing:border-box}
body{font-family:'DejaVu Sans Mono','Courier New',monospace;font-size:8px;width:48mm;margin:0 auto;padding:0;color:#000;background:#fff;font-weight:900;line-height:1.18;position:relative;left:-1.25mm;-webkit-font-smoothing:none;text-rendering:geometricPrecision}
.ticket{width:100%;padding:0;margin:0}.center{text-align:center}.bold{font-weight:900}.brand{font-size:11px;line-height:1.1}.guide{font-size:10px;margin-top:1px}.line{width:100%;border-top:1px dashed #000;margin:2px 0}.row{display:flex;justify-content:space-between;align-items:flex-start;gap:3px;margin-bottom:1px}.row span:first-child{flex-shrink:0}
table{width:100%;border-collapse:collapse;table-layout:fixed;margin-top:2px;font-weight:900}th,td{font-size:7px;text-align:left;vertical-align:top;padding:1px 1px 1px 0;font-weight:900;overflow-wrap:anywhere;border-bottom:1px dotted #999}th{border-bottom:1px solid #000}th:nth-child(1),td:nth-child(1){width:43%}th:nth-child(2),td:nth-child(2){width:14%;text-align:center}th:nth-child(3),td:nth-child(3){width:21%;text-align:right}th:nth-child(4),td:nth-child(4){width:22%;text-align:right}
.qr{text-align:center;margin:4px 0 2px}.qr img{width:100px;height:100px;image-rendering:pixelated;image-rendering:crisp-edges}.footer{text-align:center;margin-top:2px;font-size:7px}.total-row{font-size:9px;margin-top:2px}
</style>
</head>
<body><div class="ticket">
<div class="center bold brand">CatuDrive</div>
<div class="center">{{ $tipo === 'comprobante' ? 'DETALLE DE ENCOMIENDA' : 'ENCOMIENDA' }}</div>
<div class="center bold guide">{{ $encomienda->guia }}</div>
<div class="line"></div>
<div class="row"><span class="bold">Fecha:</span><span>{{ optional($encomienda->created_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</span></div>
<div class="row"><span class="bold">Estado:</span><span>{{ str_replace('_',' ', $encomienda->estado) }}</span></div>
<div class="row"><span class="bold">Estado pago:</span><span>{{ $encomienda->estado_pago ?? '-' }}</span></div>
<div class="row"><span class="bold">Ruta:</span><span>{{ $encomienda->viajeEncomienda?->viaje?->vehiculoChoferRuta?->ruta?->origen ?? '-' }} → {{ $encomienda->viajeEncomienda?->viaje?->vehiculoChoferRuta?->ruta?->destino ?? '-' }}</span></div>
<div class="row"><span class="bold">Remitente:</span><span>{{ trim(implode(' ', array_filter([$encomienda->remitente?->nombres,$encomienda->remitente?->apellido_paterno,$encomienda->remitente?->apellido_materno]))) ?: '-' }}</span></div>
<div class="row"><span class="bold">Destinatario:</span><span>{{ trim(implode(' ', array_filter([$encomienda->destinatario?->nombres,$encomienda->destinatario?->apellido_paterno,$encomienda->destinatario?->apellido_materno]))) ?: '-' }}</span></div>
<div class="line"></div>
<table><thead><tr><th>Detalle</th><th>Cant.</th><th>P/U</th><th>Subtotal</th></tr></thead><tbody>
@forelse($encomienda->detalles as $detalle)<tr><td>{{ $detalle->detalle }}</td><td>{{ $detalle->cantidad }}</td><td>Bs {{ number_format((float)$detalle->precio_unitario,2) }}</td><td>Bs {{ number_format((float)$detalle->cantidad*(float)$detalle->precio_unitario,2) }}</td></tr>@empty<tr><td colspan="4">Sin detalle registrado.</td></tr>@endforelse
</tbody></table>
<div class="line"></div>
<div class="row"><span class="bold">Cantidad:</span><span>{{ $encomienda->detalles->sum('cantidad') }}</span></div>
@if((float)($encomienda->descuento ?? 0)>0)<div class="row"><span class="bold">Descuento:</span><span>Bs {{ number_format((float)$encomienda->descuento,2) }}</span></div>@endif
<div class="row total-row"><span class="bold">TOTAL:</span><span>Bs {{ number_format((float)$encomienda->total,2) }}</span></div>
@if($tipo === 'comprobante')<div class="row"><span class="bold">Concepto:</span><span>{{ $encomienda->concepto ?: '-' }}</span></div><div class="row"><span class="bold">Viaje:</span><span>#{{ $encomienda->viajeEncomienda?->id_viaje ?? '-' }}</span></div>@endif
<div class="line"></div>
@if($tipo !== 'comprobante')<div class="qr"><img src="{{ $qrImage }}" alt="QR"></div><div class="center footer">Escanee este código para consultar la encomienda</div>@endif
<div class="center footer">Impreso: {{ now()->format('d/m/Y H:i') }}</div>
</div></body></html>
