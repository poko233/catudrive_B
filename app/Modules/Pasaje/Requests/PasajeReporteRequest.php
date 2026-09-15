<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PasajeReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],

            // Filtros específicos
            'id_ruta' => ['nullable', 'integer', 'exists:ruta,id'],
            'id_vehiculo' => ['nullable', 'integer', 'exists:vehiculo,id'],
            'id_chofer' => ['nullable', 'integer', 'exists:chofer,id'],
            'forma_pago' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', Rule::in(['Pendiente', 'Pagada', 'Anulada'])],
        ];
    }
}