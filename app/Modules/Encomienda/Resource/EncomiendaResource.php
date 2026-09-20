<?php

declare(strict_types=1);
namespace App\Modules\Encomienda\Resource;
use Illuminate\Http\Request; use Illuminate\Http\Resources\Json\JsonResource;
class EncomiendaResource extends JsonResource { public function toArray(Request $request): array { $ve=$this->viajeEncomienda;$v=$ve?->viaje;$vcr=$v?->vehiculoChoferRuta;$ruta=$vcr?->ruta;$a=$vcr?->asignacion;$u=$a?->chofer?->usuario; $cliente=fn($c)=>$c===null?null:['id'=>(int)$c->id,'nombres'=>$c->nombres,'apellido_paterno'=>$c->apellido_paterno,'apellido_materno'=>$c->apellido_materno,'nombre_completo'=>trim(implode(' ',array_filter([$c->nombres,$c->apellido_paterno,$c->apellido_materno]))),'ci'=>$c->ci,'telefono'=>$c->telefono]; return [
 'id'=>(int)$this->id,'guia'=>$this->guia,'qr_token'=>$this->qr_token,'remitente'=>$cliente($this->remitente),'destinatario'=>$cliente($this->destinatario),'concepto'=>$this->concepto,
 'detalles'=>$this->detalles->map(fn($d)=>['id'=>(int)$d->id,'detalle'=>$d->detalle,'cantidad'=>(int)$d->cantidad,'precio_unitario'=>number_format((float)$d->precio_unitario,2,'.',''),'subtotal'=>number_format((int)$d->cantidad*(float)$d->precio_unitario,2,'.','')])->values(),
 'subtotal'=>number_format((float)$this->subtotal,2,'.',''),'descuento'=>number_format((float)$this->descuento,2,'.',''),'total'=>number_format((float)$this->total,2,'.',''),'lugar_pago'=>$this->lugar_pago,'estado_pago'=>$this->estado_pago,'tipo_pago'=>$this->tipo_pago,
 'estado'=>match($this->estado){'En origen'=>'EN_ORIGEN','En tránsito'=>'EN_TRANSITO','En destino'=>'EN_DESTINO','Entregada'=>'ENTREGADA','Anulada'=>'ANULADA',default=>mb_strtoupper(str_replace(' ','_',$this->estado))},
 'usuario_registro'=>$this->usuarioRegistro?['id'=>(int)$this->usuarioRegistro->id,'nombre'=>trim(implode(' ',array_filter([$this->usuarioRegistro->nombres,$this->usuarioRegistro->primer_apellido,$this->usuarioRegistro->segundo_apellido])) )]:null,
 'origen'=>$ruta?->origen,'destino'=>$ruta?->destino,'viaje'=>$v?['id'=>(int)$v->id,'estado'=>$v->estado,'ruta'=>$ruta?['id'=>(int)$ruta->id,'origen'=>$ruta->origen,'destino'=>$ruta->destino]:null,'vehiculo'=>$a?->vehiculo?['id'=>(int)$a->vehiculo->id,'placa'=>$a->vehiculo->placa]:null,'chofer'=>$a?->chofer?['id'=>(int)$a->chofer->id,'nombre'=>trim(implode(' ',array_filter([$u?->nombres,$u?->primer_apellido,$u?->segundo_apellido])))]:null]:null,
 'qr_disponible'=>$v!==null&&!empty($this->qr_token)&&!$this->estaAnulada(),'created_at'=>$this->created_at?->toISOString(),'updated_at'=>$this->updated_at?->toISOString()]; } }
