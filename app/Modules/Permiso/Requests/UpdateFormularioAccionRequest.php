<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Requests;

use App\Shared\Models\FormularioAccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormularioAccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (
            $this->has(
                'selector_html'
            )
        ) {
            $this->merge([
                'selector_html' =>
                    trim(
                        (string)
                        $this->input(
                            'selector_html'
                        )
                    ),
            ]);
        }
    }

    public function rules(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Regla actual
        |--------------------------------------------------------------------------
        */

        $regla =
            $this->route(
                'formularioAccion'
            );

        $id =
            $regla instanceof FormularioAccion
                ? $regla->getKey()
                : (
                    is_numeric(
                        $regla
                    )
                        ? (int) $regla
                        : null
                );

        /*
        |--------------------------------------------------------------------------
        | Rol efectivo
        |--------------------------------------------------------------------------
        */

        $idRol =
            $this->integer(
                'id_rol',
                $regla instanceof FormularioAccion
                    ? (int) $regla->id_rol
                    : 0
            );

        /*
        |--------------------------------------------------------------------------
        | Formulario efectivo
        |--------------------------------------------------------------------------
        */

        $idFormulario =
            $this->integer(
                'id_formulario',
                $regla instanceof FormularioAccion
                    ? (int)
                    $regla->id_formulario
                    : 0
            );

        return [
            /*
            |--------------------------------------------------------------------------
            | Rol
            |--------------------------------------------------------------------------
            */

            'id_rol' => [
                'sometimes',
                'integer',
                'exists:rol,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Formulario
            |--------------------------------------------------------------------------
            */

            'id_formulario' => [
                'sometimes',
                'integer',
                'exists:formulario,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Selector
            |--------------------------------------------------------------------------
            */

            'selector_html' => [
                'sometimes',
                'required',
                'string',
                'max:200',

                Rule::unique(
                    'formulario_accion',
                    'selector_html'
                )
                    ->where(
                        fn ($query) =>
                            $query
                                ->where(
                                    'id_rol',
                                    $idRol
                                )
                                ->where(
                                    'id_formulario',
                                    $idFormulario
                                )
                    )
                    ->ignore(
                        $id
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Estado de Visibility
            |--------------------------------------------------------------------------
            */

            'habilitado' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_rol.integer' =>
                'El rol seleccionado no es válido.',

            'id_rol.exists' =>
                'El rol seleccionado no existe.',

            'id_formulario.integer' =>
                'El formulario seleccionado no es válido.',

            'id_formulario.exists' =>
                'El formulario seleccionado no existe.',

            'selector_html.required' =>
                'El selector es obligatorio.',

            'selector_html.string' =>
                'El selector no es válido.',

            'selector_html.max' =>
                'El selector no puede superar los 200 caracteres.',

            'selector_html.unique' =>
                'Esta regla de visibilidad ya existe para el rol y formulario.',

            'habilitado.boolean' =>
                'El estado de la regla no es válido.',
        ];
    }
}