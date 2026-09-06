<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearViajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_vehiculo_chofer_ruta' => ['required', 'integer', 'exists:vehiculo_chofer_ruta,id'],
        ];
    }
}