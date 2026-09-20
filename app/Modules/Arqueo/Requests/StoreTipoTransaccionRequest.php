<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTipoTransaccionRequest extends FormRequest
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
            'codigo' => ['required', 'string', 'max:10', 'unique:tipo_transaccion,codigo'],
            'transaccion' => ['required', 'string', 'max:80'],
            'tipo_transaccion' => ['required', Rule::in(['Ingreso', 'Egreso'])],
        ];
    }
}