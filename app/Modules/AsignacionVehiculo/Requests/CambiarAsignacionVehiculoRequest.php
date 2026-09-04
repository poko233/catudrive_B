<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CambiarAsignacionVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $observacion =
            trim(
                (string)
                $this->input(
                    'observacion',
                    ''
                )
            );

        $this->merge([
            'observacion' =>
                $observacion === ''
                    ? null
                    : $observacion,
        ]);
    }

    public function rules(): array
    {
        return [
            'fecha_asignacion' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'id_chofer' => [
                'required',
                'integer',
                'exists:chofer,id',
            ],

            'id_vehiculo' => [
                'required',
                'integer',
                'exists:vehiculo,id',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_asignacion.required' =>
                'La fecha del cambio es obligatoria.',

            'fecha_asignacion.before_or_equal' =>
                'La fecha del cambio no puede ser posterior a hoy.',

            'id_chofer.required' =>
                'Debe seleccionar un chofer.',

            'id_vehiculo.required' =>
                'Debe seleccionar un vehículo.',
        ];
    }
}