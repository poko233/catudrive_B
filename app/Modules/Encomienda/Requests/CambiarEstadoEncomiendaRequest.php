<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoEncomiendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                Rule::in(['EN_TRANSITO', 'EN_DESTINO']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe indicar el nuevo estado de la encomienda.',
            'estado.in' => 'La transición solicitada no es válida.',
        ];
    }
}
