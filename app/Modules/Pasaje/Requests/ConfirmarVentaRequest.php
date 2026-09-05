<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'forma_pago' => ['required', 'string', 'max:255'],
            'pasajeros' => ['required', 'array', 'min:1'],
            'pasajeros.*.id_detalle_venta' => ['required', 'integer', 'exists:detalle_venta,id'],
            'pasajeros.*.nombres' => ['required', 'string', 'max:255'],
            'pasajeros.*.apellido_paterno' => ['required', 'string', 'max:255'],
            'pasajeros.*.apellido_materno' => ['nullable', 'string', 'max:255'],
            'pasajeros.*.ci' => ['required', 'string', 'max:255'],
        ];
    }
}