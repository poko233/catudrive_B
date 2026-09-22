<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEncomiendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_viaje' => ['required', 'integer', 'exists:viaje,id'],
            'id_remitente' => ['required', 'integer', 'exists:cliente,id'],
            'id_destinatario' => ['required', 'integer', 'exists:cliente,id', 'different:id_remitente'],
            'concepto' => ['nullable', 'string', 'max:1000'],
            'descuento' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'lugar_pago' => ['required', 'in:Origen,Destino'],
            'estado_pago' => ['required', 'in:Pendiente,Pagado'],
            'tipo_pago' => ['nullable', 'in:Efectivo,QR,Transferencia', 'required_if:estado_pago,Pagado'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.detalle' => ['required', 'string', 'max:255'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $lugar = $this->input('lugar_pago');
            $estado = $this->input('estado_pago');

            if ($lugar === 'Origen' && $estado !== 'Pagado') {
                $validator->errors()->add('estado_pago', 'Una encomienda con pago en origen debe registrarse como pagada.');
            }

            if ($lugar === 'Destino' && $estado !== 'Pendiente') {
                $validator->errors()->add('estado_pago', 'Una encomienda con pago en destino debe registrarse inicialmente como pendiente.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'id_viaje.required' => 'Debe seleccionar un viaje.',
            'id_viaje.exists' => 'El viaje seleccionado no existe.',
            'id_remitente.required' => 'Debe seleccionar un remitente.',
            'id_remitente.exists' => 'El remitente no existe.',
            'id_destinatario.required' => 'Debe seleccionar un destinatario.',
            'id_destinatario.exists' => 'El destinatario no existe.',
            'id_destinatario.different' => 'El remitente y el destinatario deben ser clientes diferentes.',
            'detalles.required' => 'Debe registrar al menos un detalle.',
            'detalles.min' => 'Debe registrar al menos un detalle.',
            'detalles.*.detalle.required' => 'El detalle es obligatorio.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
            'detalles.*.precio_unitario.min' => 'El precio unitario no puede ser negativo.',
            'tipo_pago.required_if' => 'Debe indicar el tipo de pago cuando la encomienda está pagada.',
        ];
    }
}
