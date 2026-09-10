<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $tipo === 'comprobante' ? 'Detalle de encomienda' : 'Etiqueta de encomienda' }}</title>
<style>
@page{size:58mm auto;margin:3mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;width:52mm;margin:0 auto;color:#000;font-size:10px}.center{text-align:center}.title{font-size:14px;font-weight:700}.guide{font-size:15px;font-weight:700;margin:5px 0}.line{border-top:1px dashed #000;margin:6px 0}.row{margin:3px 0}.label{font-weight:700}.qr{width:38mm;height:38mm;display:block;margin:6px auto}.small{font-size:8px;word-break:break-all}.footer{margin-top:7px;font-size:8px}
</style>
</head>
<body>
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
</body>
</html>
