<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarEncomiendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_viaje' => [
                'required',
                'integer',
                'exists:viaje,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_viaje.required' => 'Debe seleccionar un viaje.',
            'id_viaje.integer' => 'El viaje seleccionado no es válido.',
            'id_viaje.exists' => 'El viaje seleccionado no existe.',
        ];
    }
}