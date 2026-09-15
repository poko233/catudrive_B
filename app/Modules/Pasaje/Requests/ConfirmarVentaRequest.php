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

            // Obligatorios
            'pasajeros.*.nombres' => ['required', 'string', 'max:255'],
            'pasajeros.*.apellido_paterno' => ['required', 'string', 'max:255'],

            // Opcionales
            'pasajeros.*.apellido_materno' => ['nullable', 'string', 'max:255'],
            'pasajeros.*.ci' => ['nullable', 'string', 'max:255'],

            // Precio opcional (si no viene, se mantiene el original)
            'pasajeros.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}