<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncomiendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $descripcion =
            trim(
                (string)
                $this->input(
                    'descripcion',
                    ''
                )
            );

        $this->merge([
            'remitente' =>
                trim(
                    (string)
                    $this->input(
                        'remitente',
                        ''
                    )
                ),

            'destinatario' =>
                trim(
                    (string)
                    $this->input(
                        'destinatario',
                        ''
                    )
                ),

            'origen' =>
                trim(
                    (string)
                    $this->input(
                        'origen',
                        ''
                    )
                ),

            'destino' =>
                trim(
                    (string)
                    $this->input(
                        'destino',
                        ''
                    )
                ),

            'descripcion' =>
                $descripcion === ''
                    ? null
                    : $descripcion,
        ]);
    }

    public function rules(): array
    {
        return [
            'remitente' => [
                'required',
                'string',
                'max:255',
            ],

            'destinatario' => [
                'required',
                'string',
                'max:255',
            ],

            'origen' => [
                'required',
                'string',
                'max:255',
            ],

            'destino' => [
                'required',
                'string',
                'max:255',
                'different:origen',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'cantidad' => [
                'required',
                'integer',
                'min:1',
            ],

            'precio' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'remitente.required' =>
                'El remitente es obligatorio.',

            'remitente.max' =>
                'El remitente no puede superar los 255 caracteres.',

            'destinatario.required' =>
                'El destinatario es obligatorio.',

            'destinatario.max' =>
                'El destinatario no puede superar los 255 caracteres.',

            'origen.required' =>
                'El origen es obligatorio.',

            'origen.max' =>
                'El origen no puede superar los 255 caracteres.',

            'destino.required' =>
                'El destino es obligatorio.',

            'destino.different' =>
                'El destino debe ser diferente al origen.',

            'destino.max' =>
                'El destino no puede superar los 255 caracteres.',

            'descripcion.max' =>
                'La descripción no puede superar los 2000 caracteres.',

            'cantidad.required' =>
                'La cantidad es obligatoria.',

            'cantidad.integer' =>
                'La cantidad debe ser un número entero.',

            'cantidad.min' =>
                'La cantidad debe ser mayor o igual a 1.',

            'precio.required' =>
                'El precio es obligatorio.',

            'precio.numeric' =>
                'El precio debe ser un valor numérico.',

            'precio.min' =>
                'El precio no puede ser negativo.',

            'precio.decimal' =>
                'El precio puede tener como máximo 2 decimales.',
        ];
    }
}