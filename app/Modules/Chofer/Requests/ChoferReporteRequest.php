<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChoferReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real se realiza mediante CheckPermission.
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

            'estado' => [
                'nullable',
                'string',

                Rule::in([
                    'Activo',
                    'Inactivo',
                ]),
            ],
        ];
    }
}
