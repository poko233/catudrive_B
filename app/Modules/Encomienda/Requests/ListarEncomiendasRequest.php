<?php

declare(strict_types=1);
namespace App\Modules\Encomienda\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ListarEncomiendasRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return [
  'buscar'=>['nullable','string','max:255'], 'estado'=>['nullable','in:EN_ORIGEN,EN_TRANSITO,EN_DESTINO,ENTREGADA,ANULADA'],
  'estado_pago'=>['nullable','in:PENDIENTE,PAGADO'], 'lugar_pago'=>['nullable','in:ORIGEN,DESTINO'],
  'page'=>['nullable','integer','min:1'], 'per_page'=>['nullable','integer','min:1','max:100'],
 ]; }
}
