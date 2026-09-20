<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Acepta ID (integer) o código (string) del tipo de transacción.
            'tipo_transaccion' => ['required'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'tipo_pago' => [
                'required',
                'string',
                Rule::in(['Efectivo', 'Tarjeta', 'QR', 'Transferencia']),
            ],
            'detalle' => ['required', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Si llega un `tipo_transaccion` numérico como string
        // lo tratamos como código (regla del ArqueoService).
        // El front envía siempre string para códigos.
    }
}