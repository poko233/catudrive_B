<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignacionReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real se realiza con CheckPermission.
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'fecha_fin' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:fecha_inicio',
            ],

            'estado_asignacion' => [
                'nullable',
                'string',

                Rule::in([
                    'Activo',
                    'Inactivo',
                ]),
            ],

            'estado_vehiculo' => [
                'nullable',
                'string',

                Rule::in([
                    'Operativo',
                    'En mantenimiento',
                    'Baja',
                ]),
            ],
        ];
    }
}
