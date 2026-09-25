<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListarPasajerosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:255'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
