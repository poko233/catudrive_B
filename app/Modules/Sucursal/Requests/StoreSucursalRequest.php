<?php

declare(strict_types=1);

namespace App\Modules\Sucursal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSucursalRequest extends FormRequest
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
                'sucursal',
                'responsable',
                'direccion',
                'telefono',
                'celular',
                'email',
                'pais',
                'ciudad',
                'localidad',
                'estado',
            ] as $campo
        ) {
            if (!$this->exists($campo)) {
                continue;
            }

            $valor = $this->input($campo);

            if (!is_string($valor)) {
                continue;
            }

            $valor = trim($valor);

            if (
                in_array(
                    $campo,
                    [
                        'responsable',
                        'direccion',
                        'telefono',
                        'celular',
                        'email',
                        'pais',
                        'ciudad',
                        'localidad',
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
            isset($normalizados['email']) &&
            $normalizados['email'] !== null
        ) {
            $normalizados['email'] =
                mb_strtolower(
                    $normalizados['email']
                );
        }

        if ($normalizados !== []) {
            $this->merge(
                $normalizados
            );
        }
    }

    public function rules(): array
    {
        return [
            'id_empresa' => [
                'required',
                'integer',
                'exists:empresa,id',
            ],

            'sucursal' => [
                'required',
                'string',
                'max:40',

                Rule::unique(
                    'sucursal',
                    'sucursal'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'id_empresa',
                            $this->integer(
                                'id_empresa'
                            )
                        )
                ),
            ],

            'responsable' => [
                'nullable',
                'string',
                'max:40',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:80',
            ],

            'longitud' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'latitud' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:10',
            ],

            'celular' => [
                'nullable',
                'string',
                'max:10',
            ],

            'email' => [
                'nullable',
                'email:rfc',
                'max:40',
            ],

            'pais' => [
                'nullable',
                'string',
                'max:20',
            ],

            'ciudad' => [
                'nullable',
                'string',
                'max:20',
            ],

            'localidad' => [
                'nullable',
                'string',
                'max:30',
            ],

            'imagen' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
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
            'id_empresa.required' =>
                'La empresa es obligatoria.',

            'id_empresa.exists' =>
                'La empresa seleccionada no existe.',

            'sucursal.required' =>
                'El nombre de la sucursal es obligatorio.',

            'sucursal.max' =>
                'El nombre de la sucursal no puede exceder 40 caracteres.',

            'sucursal.unique' =>
                'Ya existe una sucursal con este nombre en la empresa seleccionada.',

            'longitud.numeric' =>
                'La longitud debe ser un valor numérico.',

            'longitud.between' =>
                'La longitud debe estar entre -180 y 180.',

            'latitud.numeric' =>
                'La latitud debe ser un valor numérico.',

            'latitud.between' =>
                'La latitud debe estar entre -90 y 90.',

            'email.email' =>
                'El formato del correo electrónico no es válido.',

            'imagen.image' =>
                'El archivo seleccionado debe ser una imagen válida.',

            'imagen.mimes' =>
                'La imagen debe ser JPG, JPEG, PNG o WebP.',

            'imagen.max' =>
                'La imagen no puede superar los 5 MB.',

            'estado.in' =>
                'El estado debe ser Activo o Inactivo.',
        ];
    }
}
