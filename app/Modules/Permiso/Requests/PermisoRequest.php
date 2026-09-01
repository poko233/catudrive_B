<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Requests;

use App\Shared\Models\Accion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermisoRequest extends FormRequest
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
            | Permisos
            |--------------------------------------------------------------------------
            |
            | Un array vacío:
            |
            | []
            |
            | significa quitar todos los permisos del rol.
            |
            */

            'permisos' => [
                'present',
                'array',
            ],

            /*
            |--------------------------------------------------------------------------
            | Módulo
            |--------------------------------------------------------------------------
            */

            'permisos.*.id_modulo' => [
                'required',
                'integer',
                'exists:modulo,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Formulario
            |--------------------------------------------------------------------------
            */

            'permisos.*.id_formulario' => [
                'required',
                'integer',
                'exists:formulario,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Acción
            |--------------------------------------------------------------------------
            |
            | Solamente permitimos las acciones oficiales:
            |
            | 1 Ver
            | 2 Crear
            | 3 Editar
            | 4 Eliminar
            |
            */

            'permisos.*.id_accion' => [
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
            'permisos.present' =>
                'Debes enviar el campo permisos, aunque sea un arreglo vacío.',

            'permisos.array' =>
                'Los permisos deben ser un arreglo.',

            'permisos.*.id_modulo.required' =>
                'El módulo es obligatorio.',

            'permisos.*.id_modulo.integer' =>
                'El módulo seleccionado no es válido.',

            'permisos.*.id_modulo.exists' =>
                'El módulo no existe.',

            'permisos.*.id_formulario.required' =>
                'El formulario es obligatorio.',

            'permisos.*.id_formulario.integer' =>
                'El formulario seleccionado no es válido.',

            'permisos.*.id_formulario.exists' =>
                'El formulario no existe.',

            'permisos.*.id_accion.required' =>
                'La acción es obligatoria.',

            'permisos.*.id_accion.integer' =>
                'La acción seleccionada no es válida.',

            'permisos.*.id_accion.in' =>
                'La acción seleccionada no es válida.',

            'permisos.*.id_accion.exists' =>
                'La acción no existe.',
        ];
    }
}