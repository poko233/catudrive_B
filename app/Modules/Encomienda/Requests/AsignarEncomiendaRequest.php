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
            'id_asignacion_vehiculo_chofer' => [
                'required',
                'integer',
                'exists:asignacion_vehiculo_chofer,id',
            ],

            'id_ruta' => [
                'required',
                'integer',
                'exists:ruta,id',
            ],

            'hora_inicio' => [
                'required',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asignacion_vehiculo_chofer.required' =>
                'Debe seleccionar un vehículo con chofer asignado.',

            'id_asignacion_vehiculo_chofer.exists' =>
                'La asignación seleccionada no existe.',

            'id_ruta.required' =>
                'Debe seleccionar una ruta.',

            'id_ruta.exists' =>
                'La ruta seleccionada no existe.',

            'hora_inicio.required' =>
                'La fecha y hora de salida son obligatorias.',

            'hora_inicio.date' =>
                'La fecha y hora de salida no son válidas.',
        ];
    }
}