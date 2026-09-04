<?php

declare(strict_types=1);

namespace App\Modules\Ruta\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
                        'origen',
                        ''
                    )
                ),

            'destino' =>
                trim(
                    (string)
                    $this->input(
                        'destino',
                        ''
                    )
                ),

            'fecha_inicio' =>
                $this->nullableText(
                    $this->input(
                        'fecha_inicio'
                    )
                ),

            'hora_inicio' =>
                $this->nullableText(
                    $this->input(
                        'hora_inicio'
                    )
                ),

            'fecha_fin' =>
                $this->nullableText(
                    $this->input(
                        'fecha_fin'
                    )
                ),

            'hora_fin' =>
                $this->nullableText(
                    $this->input(
                        'hora_fin'
                    )
                ),

            'estado' =>
                mb_strtoupper(
                    trim(
                        (string)
                        $this->input(
                            'estado',
                            'ACTIVA'
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
            ],

            'fecha_inicio' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'hora_inicio' => [
                'nullable',
                'date_format:H:i',
            ],

            'fecha_fin' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'hora_fin' => [
                'nullable',
                'date_format:H:i',
            ],

            'tarifa' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'estado' => [
                'required',
                'string',
                'in:ACTIVA,INACTIVA',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (
                Validator $validator
            ): void {
                $fechaInicio =
                    $this->input(
                        'fecha_inicio'
                    );

                $fechaFin =
                    $this->input(
                        'fecha_fin'
                    );

                $horaInicio =
                    $this->input(
                        'hora_inicio'
                    );

                $horaFin =
                    $this->input(
                        'hora_fin'
                    );

                if (
                    $fechaInicio &&
                    $fechaFin &&
                    $fechaFin <
                    $fechaInicio
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'fecha_fin',
                            'La fecha de finalización no puede ser anterior a la fecha de inicio.'
                        );

                    return;
                }

                if (
                    $fechaInicio &&
                    $fechaFin &&
                    $fechaInicio ===
                    $fechaFin &&
                    $horaInicio &&
                    $horaFin &&
                    $horaFin <
                    $horaInicio
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'hora_fin',
                            'En la misma fecha, la hora de finalización no puede ser anterior a la hora de inicio.'
                        );
                }
            }
        );
    }

    public function messages(): array
    {
        return [
            'origen.required' =>
                'El origen es obligatorio.',

            'destino.required' =>
                'El destino es obligatorio.',

            'fecha_inicio.date_format' =>
                'La fecha de inicio debe tener el formato YYYY-MM-DD.',

            'fecha_fin.date_format' =>
                'La fecha de finalización debe tener el formato YYYY-MM-DD.',

            'hora_inicio.date_format' =>
                'La hora de inicio debe tener el formato HH:mm.',

            'hora_fin.date_format' =>
                'La hora de finalización debe tener el formato HH:mm.',

            'tarifa.required' =>
                'La tarifa es obligatoria.',

            'tarifa.numeric' =>
                'La tarifa debe ser numérica.',

            'tarifa.min' =>
                'La tarifa no puede ser negativa.',

            'estado.in' =>
                'El estado seleccionado no es válido.',
        ];
    }

    private function nullableText(
        mixed $value
    ): ?string {
        if (
            $value === null
        ) {
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