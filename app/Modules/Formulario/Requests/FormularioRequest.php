<?php

declare(strict_types=1);

namespace App\Modules\Formulario\Requests;

use App\Shared\Models\Formulario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormularioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza los valores antes de validarlos.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (
            [
                'formulario',
                'descripcion',
                'ruta',
                'estado',
            ] as $campo
        ) {
            if (
                !$this->exists(
                    $campo
                )
            ) {
                continue;
            }

            $valor =
                $this->input(
                    $campo
                );

            if (
                !is_string(
                    $valor
                )
            ) {
                continue;
            }

            $valor =
                trim(
                    $valor
                );

            /*
            |--------------------------------------------------------------------------
            | Campos nullable
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $campo,
                    [
                        'descripcion',
                        'ruta',
                    ],
                    true
                )
            ) {
                $data[$campo] =
                    $valor === ''
                        ? null
                        : $valor;

                continue;
            }

            $data[$campo] =
                $valor;
        }

        if (
            $data !== []
        ) {
            $this->merge(
                $data
            );
        }
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        $editing =
            $this->isMethod(
                'PUT'
            )
            ||
            $this->isMethod(
                'PATCH'
            );

        /*
        |--------------------------------------------------------------------------
        | Obtener formulario actual
        |--------------------------------------------------------------------------
        |
        | Gracias al Route Model Binding puede llegar:
        |
        | Formulario
        |
        | o solamente el ID.
        |
        */

        $routeFormulario =
            $this->route(
                'formulario'
            );

        $formularioId =
            $routeFormulario instanceof
            Formulario

                ? $routeFormulario
                    ->getKey()

                : (
                    is_numeric(
                        $routeFormulario
                    )

                        ? (int)
                        $routeFormulario

                        : null
                );

        /*
        |--------------------------------------------------------------------------
        | Crear / Editar
        |--------------------------------------------------------------------------
        */

        $formularioRules =
            $editing
                ? [
                    'sometimes',
                    'required',
                ]
                : [
                    'required',
                ];

        $estadoRules =
            $editing
                ? [
                    'sometimes',
                    'required',
                ]
                : [
                    'required',
                ];

        return [
            /*
            |--------------------------------------------------------------------------
            | Nombre
            |--------------------------------------------------------------------------
            */

            'formulario' => [
                ...$formularioRules,

                'string',

                'max:40',

                Rule::unique(
                    'formulario',
                    'formulario'
                )
                    ->ignore(
                        $formularioId
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Descripción
            |--------------------------------------------------------------------------
            */

            'descripcion' => [
                'sometimes',
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Ruta
            |--------------------------------------------------------------------------
            |
            | Ejemplos:
            |
            | /usuarios
            | /configuracion/roles
            | /recursos-humanos
            |
            */

            'ruta' => [
                'sometimes',
                'nullable',
                'string',
                'max:40',

                'regex:/^\/[A-Za-z0-9_\-\/]*$/',
            ],

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            'estado' => [
                ...$estadoRules,

                Rule::in([
                    'Activo',
                    'Inactivo',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Módulos
            |--------------------------------------------------------------------------
            */

            'modulos' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'modulos.*' => [
                'integer',
                'distinct',

                'exists:modulo,id',
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'formulario.required' =>
                'El nombre del formulario es obligatorio.',

            'formulario.string' =>
                'El nombre del formulario no es válido.',

            'formulario.max' =>
                'El nombre del formulario no puede superar los 40 caracteres.',

            'formulario.unique' =>
                'Ya existe un formulario con este nombre.',

            'ruta.max' =>
                'La ruta no puede superar los 40 caracteres.',

            'ruta.regex' =>
                'La ruta debe tener un formato como /seccion/subseccion.',

            'estado.required' =>
                'El estado es obligatorio.',

            'estado.in' =>
                'El estado debe ser Activo o Inactivo.',

            'modulos.array' =>
                'Los módulos seleccionados no son válidos.',

            'modulos.*.integer' =>
                'Uno de los módulos seleccionados no es válido.',

            'modulos.*.distinct' =>
                'No se puede seleccionar el mismo módulo más de una vez.',

            'modulos.*.exists' =>
                'Uno de los módulos seleccionados no existe.',
        ];
    }
}