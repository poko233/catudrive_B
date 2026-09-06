<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IniciarVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_viaje' => ['required', 'integer', 'exists:viaje,id'],
            'asientos' => ['required', 'array', 'min:1'],
            'asientos.*.id_asiento' => ['required', 'integer', 'exists:asiento,id'],
            'asientos.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ];
    }
}