<?php

declare(strict_types=1);

namespace App\Modules\Modulo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReordenarModulosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modulo_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'modulo_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:modulo,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'modulo_ids.required' =>
                'Debes enviar el orden de los módulos.',

            'modulo_ids.array' =>
                'El orden de módulos no es válido.',

            'modulo_ids.min' =>
                'Debe existir al menos un módulo.',

            'modulo_ids.*.integer' =>
                'Uno de los módulos no es válido.',

            'modulo_ids.*.distinct' =>
                'No puedes repetir un módulo en el orden.',

            'modulo_ids.*.exists' =>
                'Uno de los módulos ya no existe. Actualiza el listado.',
        ];
    }
}