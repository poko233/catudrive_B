<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarMovimientosRequest extends FormRequest
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
            'id_arqueo' => ['nullable', 'integer', 'exists:arqueo,id'],
            'id_user' => ['nullable', 'integer', 'exists:user,id'],
            'id_tipo_transaccion' => ['nullable', 'integer', 'exists:tipo_transaccion,id'],
            'tipo_pago' => [
                'nullable',
                Rule::in(['Efectivo', 'Tarjeta', 'QR', 'Transferencia']),
            ],
            'estado' => ['nullable', Rule::in(['Valido', 'Anulado'])],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}