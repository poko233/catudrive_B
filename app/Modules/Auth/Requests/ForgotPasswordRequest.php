<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalización
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        if (
            $this->has(
                'email'
            )
        ) {
            $this->merge([
                'email' =>
                    mb_strtolower(
                        trim(
                            (string)
                            $this->input(
                                'email'
                            )
                        )
                    ),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reglas
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:80',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Mensajes
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [
            'email.required' =>
                'El correo electrónico es obligatorio.',

            'email.string' =>
                'El correo electrónico no es válido.',

            'email.email' =>
                'Ingrese un correo electrónico válido.',

            'email.max' =>
                'El correo electrónico no puede superar los 80 caracteres.',
        ];
    }
}