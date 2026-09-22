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
            /*
            |--------------------------------------------------------------------------
            | NUEVO FLUJO
            |--------------------------------------------------------------------------
            |
            | El frontend ya no necesita crear primero una relación VCR
            | y luego seleccionar esa relación para crear el viaje.
            |
            | Ahora envía directamente:
            |
            | - id_asignacion_vehiculo_chofer
            | - id_ruta
            |
            | El backend:
            |
            | 1. Calcula automáticamente la hora.
            | 2. Crea vehiculo_chofer_ruta.
            | 3. Crea viaje.
            |
            */

            'id_asignacion_vehiculo_chofer' => [
                'required_without:id_vehiculo_chofer_ruta',
                'nullable',
                'integer',
                'exists:asignacion_vehiculo_chofer,id',
            ],

            'id_ruta' => [
                'required_without:id_vehiculo_chofer_ruta',
                'nullable',
                'integer',
                'exists:ruta,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | COMPATIBILIDAD CON EL FLUJO ANTERIOR
            |--------------------------------------------------------------------------
            |
            | Se conserva por seguridad mientras terminamos de migrar
            | completamente el frontend.
            |
            */

            'id_vehiculo_chofer_ruta' => [
                'nullable',
                'integer',
                'exists:vehiculo_chofer_ruta,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asignacion_vehiculo_chofer.required_without' =>
                'Debes seleccionar una asignación vehículo-chofer.',

            'id_asignacion_vehiculo_chofer.exists' =>
                'La asignación vehículo-chofer seleccionada no existe.',

            'id_ruta.required_without' =>
                'Debes seleccionar una ruta.',

            'id_ruta.exists' =>
                'La ruta seleccionada no existe.',

            'id_vehiculo_chofer_ruta.exists' =>
                'La relación vehículo-chofer-ruta seleccionada no existe.',
        ];
    }
}