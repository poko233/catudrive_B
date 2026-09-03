<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChoferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'carnet_sindical' =>
                trim(
                    (string)
                    $this->input('carnet_sindical')
                ),

            'nombre_completo' =>
                preg_replace(
                    '/\s+/u',
                    ' ',
                    trim(
                        (string)
                        $this->input('nombre_completo')
                    )
                ),

            'carnet_identidad' =>
                trim(
                    (string)
                    $this->input('carnet_identidad')
                ),

            'telefono' =>
                trim(
                    (string)
                    $this->input('telefono')
                ),

            'numero_licencia' =>
                trim(
                    (string)
                    $this->input('numero_licencia')
                ),

            'categoria_licencia' =>
                mb_strtoupper(
                    trim(
                        (string)
                        $this->input('categoria_licencia')
                    )
                ),

            'estado' =>
                mb_strtoupper(
                    trim(
                        (string)
                        $this->input(
                            'estado',
                            'ACTIVO'
                        )
                    )
                ),
        ]);
    }

    public function rules(): array
    {
        return [
            'carnet_sindical' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'chofer',
                    'carnet_sindical'
                ),
            ],

            'nombre_completo' => [
                'required',
                'string',
                'max:130',
            ],

            'carnet_identidad' => [
                'required',
                'string',
                'max:12',
                Rule::unique(
                    'user',
                    'ci'
                ),
            ],

            'telefono' => [
                'required',
                'string',
                'max:20',
            ],

            'numero_licencia' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'chofer',
                    'numero_licencia'
                ),
            ],

            'categoria_licencia' => [
                'required',
                'string',
                'max:255',
            ],

            'estado' => [
                'required',
                Rule::in([
                    'ACTIVO',
                    'INACTIVO',
                ]),
            ],
        ];
    }
}