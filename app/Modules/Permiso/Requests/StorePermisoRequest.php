<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Requests;

use App\Shared\Models\Accion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Módulo
            |--------------------------------------------------------------------------
            */

            'id_modulo' => [
                'bail',
                'required',
                'integer',
                'exists:modulo,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Formulario
            |--------------------------------------------------------------------------
            */

            'id_formulario' => [
                'bail',
                'required',
                'integer',
                'exists:formulario,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Acción
            |--------------------------------------------------------------------------
            */

            'id_accion' => [
                'bail',
                'required',
                'integer',

                Rule::in(
                    Accion::ids()
                ),

                'exists:accion,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_modulo.required' =>
                'El módulo es obligatorio.',

            'id_modulo.integer' =>
                'El módulo seleccionado no es válido.',

            'id_modulo.exists' =>
                'El módulo no existe.',

            'id_formulario.required' =>
                'El formulario es obligatorio.',

            'id_formulario.integer' =>
                'El formulario seleccionado no es válido.',

            'id_formulario.exists' =>
                'El formulario no existe.',

            'id_accion.required' =>
                'La acción es obligatoria.',

            'id_accion.integer' =>
                'La acción seleccionada no es válida.',

            'id_accion.in' =>
                'La acción seleccionada no es válida.',

            'id_accion.exists' =>
                'La acción no existe.',
        ];
    }
}