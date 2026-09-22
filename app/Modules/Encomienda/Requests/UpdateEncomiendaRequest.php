<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEncomiendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_remitente' => ['sometimes', 'required', 'integer', 'exists:cliente,id'],
            'id_destinatario' => ['sometimes', 'required', 'integer', 'exists:cliente,id', 'different:id_remitente'],
            'concepto' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'descuento' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'lugar_pago' => ['sometimes', 'required', 'in:Origen,Destino'],
            'estado_pago' => ['sometimes', 'required', 'in:Pendiente,Pagado'],
            'tipo_pago' => ['sometimes', 'nullable', 'in:Efectivo,QR,Transferencia'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_destinatario.different' => 'El remitente y el destinatario deben ser clientes diferentes.',
            'tipo_pago.in' => 'El tipo de pago debe ser Efectivo, QR o Transferencia.',
        ];
    }
}
