<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $tipo === 'comprobante' ? 'Detalle de encomienda' : 'Etiqueta de encomienda' }}</title>
<style id="thermal-page-style">
@page{size:80mm {{ $tipo === 'comprobante' ? '105mm' : '70mm' }};margin:0}
*{box-sizing:border-box}
html,body{margin:0!important;padding:0!important;width:80mm!important;min-width:80mm!important;background:#fff!important}
html{height:auto!important;min-height:0!important}
body{height:auto!important;min-height:0!important;font-family:Arial,sans-serif;color:#000;font-size:10px;line-height:1.12;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.ticket{display:block;width:80mm!important;height:auto!important;min-height:0!important;margin:0!important;padding:2mm 4mm!important;background:#fff;overflow:visible!important}
.center{text-align:center}.title{font-size:14px;line-height:1.05;font-weight:700}.guide{font-size:16px;line-height:1.05;font-weight:700;margin:1.5px 0}.line{border-top:1px dashed #000;margin:2.5px 0}.row{margin:1px 0;line-height:1.12}.label{font-weight:700}.qr{display:block;width:30mm;height:30mm;margin:2.5px auto 1.5px}.small{font-size:7px;line-height:1.05;word-break:break-word}.footer{margin-top:1.5px;font-size:7px;line-height:1.05}
@media print{
    html,body{margin:0!important;padding:0!important;width:80mm!important;min-width:80mm!important;min-height:0!important;background:#fff!important;overflow:visible!important}
    body{position:static!important;display:block!important}
    .ticket{position:static!important;display:block!important;width:80mm!important;height:auto!important;min-height:0!important;margin:0!important;padding:2mm 4mm!important;overflow:visible!important;break-inside:avoid!important;page-break-inside:avoid!important}
    img{max-width:100%!important;break-inside:avoid!important;page-break-inside:avoid!important}
}
</style>
<script>
(function(){
    var MM_PER_PX = 25.4 / 96;
    var MIN_ETIQUETA_MM = 55;
    var MIN_COMPROBANTE_MM = 75;
    var MAX_MM = 120;
    var tipo = @json($tipo);

    function aplicarTamanoExacto(){
        var ticket = document.getElementById('thermal-ticket');
        var style = document.getElementById('thermal-page-dynamic');
        if(!ticket){ return null; }

        /*
         * Medimos SOLO el contenido real. No usamos el alto del viewport,
         * iframe ni papel configurado por el navegador.
         */
        var altoPx = Math.ceil(ticket.getBoundingClientRect().height || ticket.scrollHeight || 0);
        var minimo = tipo === 'comprobante' ? MIN_COMPROBANTE_MM : MIN_ETIQUETA_MM;
        var altoMm = Math.ceil((altoPx * MM_PER_PX) + 1.5);
        altoMm = Math.max(minimo, Math.min(MAX_MM, altoMm));

        if(!style){
            style = document.createElement('style');
            style.id = 'thermal-page-dynamic';
            document.head.appendChild(style);
        }

        style.textContent =
            '@page{size:80mm ' + altoMm + 'mm!important;margin:0!important;}' +
            '@media print{' +
                'html,body{width:80mm!important;height:' + altoMm + 'mm!important;min-height:0!important;max-height:' + altoMm + 'mm!important;margin:0!important;padding:0!important;overflow:hidden!important;}' +
                '#thermal-ticket{position:absolute!important;top:0!important;left:0!important;width:80mm!important;height:auto!important;min-height:0!important;max-height:' + altoMm + 'mm!important;margin:0!important;overflow:hidden!important;}' +
            '}';

        document.documentElement.style.height = altoMm + 'mm';
        document.body.style.height = altoMm + 'mm';
        document.body.style.minHeight = '0';

        return altoMm;
    }

    window.prepareThermalPrint = aplicarTamanoExacto;

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', function(){
            requestAnimationFrame(function(){
                requestAnimationFrame(aplicarTamanoExacto);
            });
        });
    }else{
        requestAnimationFrame(function(){
            requestAnimationFrame(aplicarTamanoExacto);
        });
    }

    window.addEventListener('beforeprint', aplicarTamanoExacto);
})();
</script>
</head>
<body>
<div class="ticket" id="thermal-ticket">
<div class="center title">CATUDRIVE</div>
<div class="center">{{ $tipo === 'comprobante' ? 'DETALLE DE ENCOMIENDA' : 'ENCOMIENDA' }}</div>
<div class="center guide">{{ $encomienda->guia }}</div>
<div class="line"></div>
<div class="row"><span class="label">Ruta:</span> {{ $encomienda->viajeEncomienda?->viaje?->vehiculoChoferRuta?->ruta?->origen ?? '-' }} → {{ $encomienda->viajeEncomienda?->viaje?->vehiculoChoferRuta?->ruta?->destino ?? '-' }}</div>
<div class="row"><span class="label">Remitente:</span> {{ trim(implode(' ', array_filter([$encomienda->remitente?->nombres, $encomienda->remitente?->apellido_paterno, $encomienda->remitente?->apellido_materno]))) }}</div>
<div class="row"><span class="label">Destinatario:</span> {{ trim(implode(' ', array_filter([$encomienda->destinatario?->nombres, $encomienda->destinatario?->apellido_paterno, $encomienda->destinatario?->apellido_materno]))) }}</div>
<div class="row"><span class="label">Cantidad:</span> {{ $encomienda->detalles->sum('cantidad') }}</div>
<div class="row"><span class="label">Precio:</span> Bs. {{ number_format((float) $encomienda->total, 2) }}</div>
@if($tipo === 'comprobante')
<div class="row"><span class="label">Descripción:</span> {{ $encomienda->concepto ?: '-' }}</div>
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
