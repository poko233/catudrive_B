<?php

declare(strict_types=1);

namespace App\Modules\Vehiculo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehiculoId = $this->route('vehiculo')?->id;

        return [
            'id_categoria' => ['required', 'integer', 'exists:categoria_vehiculo,id'],
            'placa' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vehiculo', 'placa')->ignore($vehiculoId),
            ],
            'tipo' => ['required', 'string', 'max:255'],
            'marca' => ['required', 'string', 'max:255'],
            'modelo' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(['Operativo', 'En mantenimiento', 'Baja'])],

            'pisos' => ['required', 'array', 'min:1'],
            'pisos.*.id' => ['sometimes', 'integer', 'exists:piso,id'],
            'pisos.*.numero' => ['required', 'integer', 'min:1'],
            'pisos.*.nombre' => ['required', 'string', 'max:255'],
            'pisos.*.filas' => ['required', 'integer', 'min:1'],
            'pisos.*.columnas' => ['required', 'integer', 'min:1'],
            'pisos.*.orden' => ['sometimes', 'integer', 'min:0'],
            'pisos.*.estado' => ['required', 'string', Rule::in(['Activo', 'Inactivo'])],

            'pisos.*.asientos' => ['required', 'array'],
            'pisos.*.asientos.*.id' => ['sometimes', 'integer', 'exists:asiento,id'],
            'pisos.*.asientos.*.fila' => ['required', 'integer', 'min:1'],
            'pisos.*.asientos.*.columna' => ['required', 'integer', 'min:1'],
            'pisos.*.asientos.*.tipo_celda' => [
                'required',
                'string',
                Rule::in(['pasajero', 'conductor', 'escaleras', 'no_disponible', 'pasillo']),
            ],
            'pisos.*.asientos.*.numero_asiento' => ['nullable', 'integer', 'min:1'],
            'pisos.*.asientos.*.estado' => ['required', 'string', Rule::in(['Activo', 'Inactivo'])],
        ];
    }
}