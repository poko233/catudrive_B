<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehiculoReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real se hace en el middleware CheckPermission
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'estado' => ['nullable', 'string', Rule::in(['Operativo', 'En mantenimiento', 'Baja'])],
            'id_categoria' => ['nullable', 'integer', 'exists:categoria_vehiculo,id'],
            'id_chofer' => ['nullable', 'integer', 'exists:chofer,id'],
        ];
    }
}