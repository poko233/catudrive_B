<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehiculoChoferRutaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asignacion_vehiculo_chofer' => ['required', 'integer', 'exists:asignacion_vehiculo_chofer,id'],
            'id_ruta' => ['required', 'integer', 'exists:ruta,id'],
            'hora_inicio' => ['nullable', 'date'],
        ];
    }
}