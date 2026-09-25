<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePasajeroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'ci' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
        ];
    }
}
