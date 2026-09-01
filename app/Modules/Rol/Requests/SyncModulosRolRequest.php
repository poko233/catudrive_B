<?php

declare(strict_types=1);

namespace App\Modules\Rol\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncModulosRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /*
             * [] es válido y significa quitar todos los módulos.
             */
            'modulo_ids' => [
                'present',
                'array',
            ],

            'modulo_ids.*' => [
                'integer',
                'distinct',
                'exists:modulo,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'modulo_ids.present' =>
                'Debes enviar el campo modulo_ids, aunque sea un array vacío.',

            'modulo_ids.array' =>
                'El campo modulo_ids debe ser un array.',

            'modulo_ids.*.integer' =>
                'Uno de los módulos enviados no es válido.',

            'modulo_ids.*.distinct' =>
                'No puedes enviar el mismo módulo más de una vez.',

            'modulo_ids.*.exists' =>
                'Uno de los módulos enviados no existe.',
        ];
    }
}
