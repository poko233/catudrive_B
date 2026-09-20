<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarTiposTransaccionRequest extends FormRequest
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
            'tipo_transaccion' => ['nullable', Rule::in(['Ingreso', 'Egreso'])],
            'buscar' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}