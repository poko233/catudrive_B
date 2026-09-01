<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'bail',
                'required',
                'string',
                'max:255',
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

    public function messages(): array
    {
        return [
            'current_password.required' =>
                'La contraseña actual es obligatoria.',

            'current_password.string' =>
                'La contraseña actual no es válida.',

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
