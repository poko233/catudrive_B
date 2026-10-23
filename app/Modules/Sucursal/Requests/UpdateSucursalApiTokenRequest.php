<?php

declare(strict_types=1);

namespace App\Modules\Sucursal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSucursalApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->exists('api_token')) {
            return;
        }

        $value = $this->input('api_token');

        if (!is_string($value)) {
            return;
        }

        $value = trim($value);

        $this->merge([
            'api_token' => $value === '' ? null : $value,
        ]);
    }

    public function rules(): array
    {
        return [
            'api_token' => [
                'present',
                'nullable',
                'string',
                'max:512',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'api_token.present' =>
                'Debe enviar el campo api_token (use null para limpiar).',
            'api_token.string' =>
                'El token debe ser una cadena de texto.',
            'api_token.max' =>
                'El token no puede superar los 512 caracteres.',
        ];
    }
}