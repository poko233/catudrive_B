<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEgresoRequest extends FormRequest
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
}