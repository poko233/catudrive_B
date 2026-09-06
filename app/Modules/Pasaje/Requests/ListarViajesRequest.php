<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListarViajesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origen' => ['nullable', 'string', 'max:255'],
            'destino' => ['nullable', 'string', 'max:255'],
            'fecha' => ['nullable', 'date'],
            'estado' => ['nullable', 'string', 'in:Vendiendo,En curso,Finalizado,Cancelado'],
            'vehiculo_id' => ['nullable', 'integer', 'exists:vehiculo,id'],
            'chofer_id' => ['nullable', 'integer', 'exists:chofer,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}