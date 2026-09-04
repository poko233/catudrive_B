<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizarAsignacionVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_finalizacion' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_finalizacion.required' =>
                'La fecha de finalización es obligatoria.',

            'fecha_finalizacion.date_format' =>
                'La fecha debe tener el formato YYYY-MM-DD.',

            'fecha_finalizacion.before_or_equal' =>
                'La fecha de finalización no puede ser posterior a hoy.',
        ];
    }
}