<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('usuario')) {
            $this->merge([
                'usuario' => trim((string) $this->input('usuario')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'usuario' => [
                'bail',
                'required',
                'string',
                'max:40',
            ],

            'password' => [
                'bail',
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'El usuario es obligatorio.',
            'usuario.string' => 'El usuario no es válido.',
            'usuario.max' => 'El usuario no puede superar los 40 caracteres.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña no es válida.',
            'password.max' => 'La contraseña no es válida.',
        ];
    }
}
