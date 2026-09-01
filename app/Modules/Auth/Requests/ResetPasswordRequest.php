<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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

        if (
            $this->has(
                'code'
            )
        ) {
            $this->merge([
                'code' =>
                    trim(
                        (string)
                        $this->input(
                            'code'
                        )
                    ),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reglas
    |--------------------------------------------------------------------------
    |
    | Mantenemos la misma política de contraseña que ya usamos
    | en ChangePasswordRequest:
    |
    | - mínimo 8 caracteres;
    | - debe contener letras;
    | - debe contener números;
    | - sin espacios;
    | - máximo 128;
    | - confirmación obligatoria.
    |
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

            'code' => [
                'bail',
                'required',
                'string',
                'digits:6',
            ],

            'new_password' => [
                'bail',
                'required',
                'string',
                'max:128',
                'not_regex:/\s/',
                'confirmed',

                Password::min(8)
                    ->letters()
                    ->numbers(),
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

            'email.email' =>
                'Ingrese un correo electrónico válido.',

            'email.max' =>
                'El correo electrónico no puede superar los 80 caracteres.',

            'code.required' =>
                'El código de recuperación es obligatorio.',

            'code.digits' =>
                'El código debe tener exactamente 6 dígitos.',

            'new_password.required' =>
                'La nueva contraseña es obligatoria.',

            'new_password.string' =>
                'La nueva contraseña no es válida.',

            'new_password.max' =>
                'La nueva contraseña no puede superar los 128 caracteres.',

            'new_password.not_regex' =>
                'La nueva contraseña no puede contener espacios.',

            'new_password.confirmed' =>
                'Las contraseñas no coinciden.',
        ];
    }
}