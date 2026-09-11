<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $tipo === 'comprobante' ? 'Detalle de encomienda' : 'Etiqueta de encomienda' }}</title>
<style>
@page{size:80mm {{ $tipo === 'comprobante' ? '115mm' : '82mm' }};margin:0}
*{box-sizing:border-box}
html,body{margin:0!important;padding:0!important;width:80mm!important;background:#fff!important}
body{font-family:Arial,sans-serif;color:#000;font-size:10px;line-height:1.12;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.ticket{width:80mm;margin:0!important;padding:2mm 4mm!important;background:#fff;overflow:hidden;break-inside:avoid;page-break-inside:avoid}
.center{text-align:center}.title{font-size:14px;line-height:1.05;font-weight:700}.guide{font-size:16px;line-height:1.05;font-weight:700;margin:1.5px 0}.line{border-top:1px dashed #000;margin:2.5px 0}.row{margin:1px 0;line-height:1.12}.label{font-weight:700}.qr{display:block;width:30mm;height:30mm;margin:2.5px auto 1.5px}.small{font-size:7px;line-height:1.05;word-break:break-word}.footer{margin-top:1.5px;font-size:7px;line-height:1.05}
@media print{
    html,body{width:80mm!important;height:{{ $tipo === 'comprobante' ? '115mm' : '82mm' }}!important;min-height:0!important;max-height:{{ $tipo === 'comprobante' ? '115mm' : '82mm' }}!important;margin:0!important;padding:0!important;overflow:hidden!important}
    body{position:relative!important}
    .ticket{position:absolute!important;top:0!important;left:0!important;width:80mm!important;height:auto!important;max-height:{{ $tipo === 'comprobante' ? '115mm' : '82mm' }}!important;margin:0!important;padding:2mm 4mm!important;overflow:hidden!important;break-inside:avoid!important;page-break-inside:avoid!important}
    img{max-width:100%!important;break-inside:avoid!important;page-break-inside:avoid!important}
}
</style>
</head>
<body>
<div class="ticket">
<div class="center title">CATUDRIVE</div>
<div class="center">{{ $tipo === 'comprobante' ? 'DETALLE DE ENCOMIENDA' : 'ENCOMIENDA' }}</div>
<div class="center guide">{{ $encomienda->guia }}</div>
<div class="line"></div>
<div class="row"><span class="label">Ruta:</span> {{ $encomienda->ruta?->origen ?? '-' }} → {{ $encomienda->ruta?->destino ?? '-' }}</div>
<div class="row"><span class="label">Remitente:</span> {{ $encomienda->remitente }}</div>
<div class="row"><span class="label">Destinatario:</span> {{ $encomienda->destinatario }}</div>
<div class="row"><span class="label">Cantidad:</span> {{ $encomienda->cantidad }}</div>
<div class="row"><span class="label">Precio:</span> Bs. {{ number_format((float) $encomienda->precio, 2) }}</div>
@if($tipo === 'comprobante')
<div class="row"><span class="label">Descripción:</span> {{ $encomienda->descripcion ?: '-' }}</div>
<div class="row"><span class="label">Estado:</span> {{ $encomienda->estado }}</div>
<div class="row"><span class="label">Viaje:</span> #{{ $encomienda->viajeEncomienda?->id_viaje ?? '-' }}</div>
@endif
<div class="line"></div>
@if($tipo !== 'comprobante')
<img class="qr" src="{{ $qrImage }}" alt="QR">
<div class="center small">Escanee este código para consultar la encomienda</div>
@endif
<div class="center footer">Impreso: {{ now()->format('d/m/Y H:i') }}</div>
</div>
</body>
</html>
