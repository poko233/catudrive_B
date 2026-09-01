<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyResetCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => mb_strtolower(
                    trim(
                        (string) $this->input('email')
                    )
                ),
            ]);
        }

        if ($this->has('code')) {
            $this->merge([
                'code' => trim(
                    (string) $this->input('code')
                ),
            ]);
        }
    }

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
        ];
    }

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
                'Ingrese el código de recuperación.',

            'code.digits' =>
                'El código debe tener exactamente 6 dígitos.',
        ];
    }
}