<?php

declare(strict_types=1);
namespace App\Modules\Encomienda\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ListarEncomiendasRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return [
  'buscar'=>['nullable','string','max:255'], 'estado'=>['nullable','in:EN_ORIGEN,EN_TRANSITO,EN_DESTINO,ENTREGADA,ANULADA'],
  'estado_pago'=>['nullable','in:PENDIENTE,PAGADO,Pendiente,Pagado'], 'tipo_pago'=>['nullable','in:Efectivo,QR,Transferencia'], 'lugar_pago'=>['nullable','in:ORIGEN,DESTINO,Origen,Destino'],
  'id_cliente'=>['nullable','integer','min:1'], 'id_chofer'=>['nullable','integer','min:1'],
  'fecha_desde'=>['nullable','date'], 'fecha_hasta'=>['nullable','date','after_or_equal:fecha_desde'],
  'page'=>['nullable','integer','min:1'], 'per_page'=>['nullable','integer','min:1','max:100'],
 ]; }
}
