<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $tipo === 'comprobante' ? 'Detalle de encomienda' : 'Etiqueta de encomienda' }}</title>
<style>
@page{size:80mm {{ $tipo === 'comprobante' ? '115mm' : '92mm' }};margin:0}*{box-sizing:border-box}html,body{margin:0!important;padding:0!important;background:#fff!important;width:80mm!important;min-width:80mm!important;overflow:visible!important}body{font-family:Arial,sans-serif;color:#000;font-size:11px;line-height:1.25;-webkit-print-color-adjust:exact;print-color-adjust:exact}.ticket{width:80mm;margin:0 auto;padding:4mm 5mm 3mm;break-inside:avoid;page-break-inside:avoid}.center{text-align:center}.title{font-size:15px;font-weight:700}.guide{font-size:17px;font-weight:700;margin:4px 0}.line{border-top:1px dashed #000;margin:5px 0}.row{margin:2px 0}.label{font-weight:700}.qr{width:40mm;height:40mm;display:block;margin:6px auto 4px}.small{font-size:8px;word-break:break-all}.footer{margin-top:5px;font-size:8px}@media print{html,body{width:80mm!important;height:auto!important}.ticket{width:80mm!important;margin:0!important;padding:3mm 5mm!important}img{max-width:100%!important;page-break-inside:avoid!important}body{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}}
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
