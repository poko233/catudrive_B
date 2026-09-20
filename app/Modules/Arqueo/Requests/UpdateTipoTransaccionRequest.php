<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTipoTransaccionRequest extends FormRequest
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
        $id = $this->route('tipoTransaccion')?->id;

        return [
            'codigo' => [
                'sometimes',
                'string',
                'max:10',
                Rule::unique('tipo_transaccion', 'codigo')->ignore($id),
            ],
            'transaccion' => ['sometimes', 'string', 'max:80'],
            'tipo_transaccion' => ['sometimes', Rule::in(['Ingreso', 'Egreso'])],
        ];
    }
}