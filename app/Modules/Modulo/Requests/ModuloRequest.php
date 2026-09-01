<?php

declare(strict_types=1);

namespace App\Modules\Modulo\Requests;

use App\Shared\Models\Modulo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizados = [];

        foreach (
            [
                'modulo',
                'descripcion',
                'icono',
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

            if (
                in_array(
                    $campo,
                    [
                        'descripcion',
                        'icono',
                    ],
                    true
                )
            ) {
                $normalizados[$campo] =
                    $valor === ''
                        ? null
                        : $valor;

                continue;
            }

            $normalizados[$campo] =
                $valor;
        }

        if (
            $normalizados !== []
        ) {
            $this->merge(
                $normalizados
            );
        }
    }

    public function rules(): array
    {
        $editing =
            $this->isMethod('PUT')
            ||
            $this->isMethod('PATCH');

        $routeModulo =
            $this->route(
                'modulo'
            );

        $moduloId =
            $routeModulo instanceof Modulo
                ? $routeModulo->getKey()
                : (
                    is_numeric(
                        $routeModulo
                    )
                        ? (int) $routeModulo
                        : null
                );

        return [
            'modulo' => [
                $editing
                    ? 'sometimes'
                    : 'required',

                'required',
                'string',
                'max:40',

                Rule::unique(
                    'modulo',
                    'modulo'
                )->ignore(
                    $moduloId
                ),
            ],

            'descripcion' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'icono' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'estado' => [
                $editing
                    ? 'sometimes'
                    : 'required',

                'required',

                Rule::in([
                    'Activo',
                    'Inactivo',
                ]),
            ],

            'formularios' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'formularios.*' => [
                'integer',
                'distinct',
                'exists:formulario,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'modulo.required' =>
                'El nombre del módulo es obligatorio.',

            'modulo.string' =>
                'El nombre del módulo no es válido.',

            'modulo.max' =>
                'El nombre del módulo no puede superar los 40 caracteres.',

            'modulo.unique' =>
                'Ya existe un módulo con este nombre.',

            'icono.max' =>
                'El identificador del ícono no puede superar los 100 caracteres.',

            'estado.required' =>
                'El estado es obligatorio.',

            'estado.in' =>
                'El estado debe ser Activo o Inactivo.',

            'formularios.array' =>
                'Los formularios seleccionados no son válidos.',

            'formularios.*.integer' =>
                'Uno de los formularios seleccionados no es válido.',

            'formularios.*.distinct' =>
                'No se puede seleccionar el mismo formulario más de una vez.',

            'formularios.*.exists' =>
                'Uno de los formularios seleccionados no existe.',
        ];
    }
}