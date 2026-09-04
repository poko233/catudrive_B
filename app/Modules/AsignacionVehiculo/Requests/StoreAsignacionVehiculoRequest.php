<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsignacionVehiculoRequest extends FormRequest
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
                'La fecha de asignación es obligatoria.',

            'fecha_asignacion.date_format' =>
                'La fecha debe tener el formato YYYY-MM-DD.',

            'fecha_asignacion.before_or_equal' =>
                'La fecha de asignación no puede ser posterior a hoy.',

            'id_chofer.required' =>
                'Debe seleccionar un chofer.',

            'id_chofer.exists' =>
                'El chofer seleccionado no existe.',

            'id_vehiculo.required' =>
                'Debe seleccionar un vehículo.',

            'id_vehiculo.exists' =>
                'El vehículo seleccionado no existe.',

            'observacion.max' =>
                'La observación no puede superar los 2000 caracteres.',
        ];
    }
}