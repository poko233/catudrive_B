<?php

declare(strict_types=1);

namespace App\Modules\Modulo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncFormulariosModuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'formulario_ids' => [
                'present',
                'array',
            ],

            'formulario_ids.*' => [
                'integer',
                'distinct',
                'exists:formulario,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'formulario_ids.present' =>
                'Debes enviar el campo formulario_ids, aunque sea un array vacío.',

            'formulario_ids.array' =>
                'El campo formulario_ids debe ser un array.',

            'formulario_ids.*.integer' =>
                'Uno de los formularios enviados no es válido.',

            'formulario_ids.*.distinct' =>
                'No puedes enviar el mismo formulario más de una vez.',

            'formulario_ids.*.exists' =>
                'Uno de los formularios enviados no existe.',
        ];
    }
}
