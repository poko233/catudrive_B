<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EncomiendaReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => [
                'nullable',
                'date',
            ],

            'fecha_fin' => [
                'nullable',
                'date',
                'after_or_equal:fecha_inicio',
            ],

            'id_ruta' => [
                'nullable',
                'integer',
                'exists:ruta,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_inicio.date' =>
                'La fecha inicial no es válida.',

            'fecha_fin.date' =>
                'La fecha final no es válida.',

            'fecha_fin.after_or_equal' =>
                'La fecha final debe ser igual o posterior a la fecha inicial.',

            'id_ruta.integer' =>
                'La ruta seleccionada no es válida.',

            'id_ruta.exists' =>
                'La ruta seleccionada no existe.',
        ];
    }
}