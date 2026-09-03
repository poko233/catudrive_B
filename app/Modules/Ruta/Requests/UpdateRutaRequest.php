<?php

declare(strict_types=1);

namespace App\Modules\Ruta\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRutaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'origen' =>
                trim(
                    (string)
                    $this->input(
                        'origen'
                    )
                ),

            'destino' =>
                trim(
                    (string)
                    $this->input(
                        'destino'
                    )
                ),

            'hora_inicio' =>
                $this->normalizarFecha(
                    $this->input(
                        'hora_inicio'
                    )
                ),

            'hora_fin' =>
                $this->normalizarFecha(
                    $this->input(
                        'hora_fin'
                    )
                ),

            'estado' =>
                mb_strtoupper(
                    trim(
                        (string)
                        $this->input(
                            'estado'
                        )
                    )
                ),
        ]);
    }

    public function rules(): array
    {
        return [
            'origen' => [
                'required',
                'string',
                'max:255',
            ],

            'destino' => [
                'required',
                'string',
                'max:255',
                'different:origen',
            ],

            'hora_inicio' => [
                'nullable',

                'required_with:hora_fin',

                'date_format:Y-m-d H:i',
            ],

            'hora_fin' => [
                'nullable',

                'required_with:hora_inicio',

                'date_format:Y-m-d H:i',

                'after_or_equal:hora_inicio',
            ],

            'tarifa' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'estado' => [
                'required',

                Rule::in([
                    'ACTIVA',
                    'INACTIVA',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'origen.required' =>
                'El origen es obligatorio.',

            'destino.required' =>
                'El destino es obligatorio.',

            'destino.different' =>
                'El destino debe ser diferente al origen.',

            'hora_inicio.required_with' =>
                'Debe registrar también la hora de inicio.',

            'hora_inicio.date_format' =>
                'La fecha y hora de inicio debe tener el formato YYYY-MM-DD HH:mm.',

            'hora_fin.required_with' =>
                'Debe registrar también la hora de finalización.',

            'hora_fin.date_format' =>
                'La fecha y hora de finalización debe tener el formato YYYY-MM-DD HH:mm.',

            'hora_fin.after_or_equal' =>
                'La hora de finalización debe ser posterior a la hora de inicio.',

            'tarifa.required' =>
                'La tarifa es obligatoria.',

            'tarifa.numeric' =>
                'La tarifa debe ser un valor numérico.',

            'tarifa.min' =>
                'La tarifa no puede ser negativa.',

            'estado.in' =>
                'El estado debe ser ACTIVA o INACTIVA.',
        ];
    }

    private function normalizarFecha(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string)
                $value
            );

        return
            $value === ''
                ? null
                : $value;
    }
}