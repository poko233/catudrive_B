<?php

declare(strict_types=1);

namespace App\Modules\Rol\Requests;

use App\Shared\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->exists('rol')) {
            $data['rol'] =
                trim(
                    (string) $this->input('rol')
                );
        }

        if ($this->exists('descripcion')) {
            $descripcion =
                trim(
                    (string)
                    $this->input('descripcion')
                );

            $data['descripcion'] =
                $descripcion === ''
                    ? null
                    : $descripcion;
        }

        if ($this->exists('estado')) {
            $data['estado'] =
                trim(
                    (string) $this->input('estado')
                );
        }

        if ($data !== []) {
            $this->merge(
                $data
            );
        }
    }

    public function rules(): array
    {
        $routeRol =
            $this->route(
                'rol'
            );

        $rolId =
            $routeRol instanceof Rol
                ? $routeRol->getKey()
                : (
                    is_numeric(
                        $routeRol
                    )
                        ? (int) $routeRol
                        : null
                );

        return [
            'rol' => [
                'sometimes',
                'required',
                'string',
                'max:40',

                Rule::unique(
                    'rol',
                    'rol'
                )->ignore(
                    $rolId
                ),
            ],

            'descripcion' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'estado' => [
                'sometimes',

                Rule::in([
                    'Activo',
                    'Inactivo',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rol.required' =>
                'El nombre del rol es obligatorio.',

            'rol.max' =>
                'El nombre del rol no puede superar los 40 caracteres.',

            'rol.unique' =>
                'Ya existe un rol con este nombre.',

            'estado.in' =>
                'El estado debe ser Activo o Inactivo.',
        ];
    }
}