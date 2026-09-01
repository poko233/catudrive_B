<?php

declare(strict_types=1);

namespace App\Modules\Sucursal\Requests;

use App\Shared\Models\Sucursal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza los datos recibidos
     * antes de realizar la validación.
     */
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
            | Campos que pueden quedar null
            |--------------------------------------------------------------------------
            */

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

        /*
        |--------------------------------------------------------------------------
        | Email normalizado
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $normalizados['email']
            ) &&
            $normalizados['email'] !== null
        ) {
            $normalizados['email'] =
                mb_strtolower(
                    $normalizados['email']
                );
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
        /*
        |--------------------------------------------------------------------------
        | Sucursal actual
        |--------------------------------------------------------------------------
        |
        | Con Route Model Binding normalmente recibimos directamente
        | una instancia de Shared\Models\Sucursal.
        |
        */

        $routeSucursal =
            $this->route(
                'sucursal'
            );

        $sucursal =
            $routeSucursal instanceof Sucursal
                ? $routeSucursal
                : (
                    is_numeric(
                        $routeSucursal
                    )
                        ? Sucursal::query()
                            ->find(
                                (int)
                                $routeSucursal
                            )
                        : null
                );

        /*
        |--------------------------------------------------------------------------
        | ID actual
        |--------------------------------------------------------------------------
        */

        $idSucursal =
            $sucursal?->getKey();

        /*
        |--------------------------------------------------------------------------
        | Empresa efectiva
        |--------------------------------------------------------------------------
        |
        | Si cambia id_empresa utilizamos la nueva.
        | De lo contrario utilizamos la actual.
        |
        */

        $idEmpresa =
            $this->exists(
                'id_empresa'
            )
                ? $this->integer(
                    'id_empresa'
                )
                : (
                    $sucursal
                        ? (int)
                        $sucursal->id_empresa
                        : 0
                );

        return [
            /*
            |--------------------------------------------------------------------------
            | Empresa
            |--------------------------------------------------------------------------
            */

            'id_empresa' => [
                'sometimes',
                'required',
                'integer',
                'exists:empresa,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Nombre
            |--------------------------------------------------------------------------
            |
            | El nombre debe ser único dentro de la misma empresa.
            |
            */

            'sucursal' => [
                'sometimes',
                'required',
                'string',
                'max:40',

                Rule::unique(
                    'sucursal',
                    'sucursal'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'id_empresa',
                                $idEmpresa
                            )
                    )
                    ->ignore(
                        $idSucursal
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Responsable
            |--------------------------------------------------------------------------
            */

            'responsable' => [
                'sometimes',
                'nullable',
                'string',
                'max:40',
            ],

            /*
            |--------------------------------------------------------------------------
            | Dirección
            |--------------------------------------------------------------------------
            */

            'direccion' => [
                'sometimes',
                'nullable',
                'string',
                'max:80',
            ],

            /*
            |--------------------------------------------------------------------------
            | Coordenadas
            |--------------------------------------------------------------------------
            */

            'longitud' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'latitud' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            /*
            |--------------------------------------------------------------------------
            | Teléfonos
            |--------------------------------------------------------------------------
            */

            'telefono' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],

            'celular' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],

            /*
            |--------------------------------------------------------------------------
            | Email
            |--------------------------------------------------------------------------
            */

            'email' => [
                'sometimes',
                'nullable',
                'email:rfc',
                'max:40',
            ],

            /*
            |--------------------------------------------------------------------------
            | Ubicación
            |--------------------------------------------------------------------------
            */

            'pais' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'ciudad' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'localidad' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            /*
            |--------------------------------------------------------------------------
            | Imagen
            |--------------------------------------------------------------------------
            */

            'imagen' => [
                'sometimes',
                'nullable',
                'file',
                'image',

                'mimes:jpg,jpeg,png,webp',

                'max:5120',
            ],

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

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