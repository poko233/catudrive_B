<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoriaVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización real se hace en el middleware CheckPermission
    }

    public function rules(): array
    {
        return [
            'categoria' => [
                'required',
                'string',
                'max:255',
                'unique:categoria_vehiculo,categoria',
            ],
        ];
    }
}