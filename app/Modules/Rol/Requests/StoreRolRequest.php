<?php

declare(strict_types=1);

namespace App\Modules\Rol\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRolRequest extends FormRequest
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
                    (string)
                    $this->input('rol')
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
                    (string)
                    $this->input('estado')
                );
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'rol' => [
                'required',
                'string',
                'max:40',

                Rule::unique(
                    'rol',
                    'rol'
                ),
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'estado' => [
                'nullable',

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
