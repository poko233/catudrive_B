<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoriaId = $this->route('categoria_vehiculo')?->id;

        return [
            'categoria' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categoria_vehiculo', 'categoria')->ignore($categoriaId),
            ],
        ];
    }
}