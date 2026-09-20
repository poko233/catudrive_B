<?php

declare(strict_types=1);

namespace App\Modules\Arqueo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CerrarArqueoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'total_efectivo' => ['nullable', 'numeric', 'min:0'],
            'total_tarjeta' => ['nullable', 'numeric', 'min:0'],
            'total_qr' => ['nullable', 'numeric', 'min:0'],
            'total_transferencia' => ['nullable', 'numeric', 'min:0'],
            'total_general' => ['nullable', 'numeric', 'min:0'],

            'billete_200' => ['nullable', 'integer', 'min:0'],
            'billete_100' => ['nullable', 'integer', 'min:0'],
            'billete_50' => ['nullable', 'integer', 'min:0'],
            'billete_20' => ['nullable', 'integer', 'min:0'],
            'billete_10' => ['nullable', 'integer', 'min:0'],

            'moneda_5' => ['nullable', 'integer', 'min:0'],
            'moneda_2' => ['nullable', 'integer', 'min:0'],
            'moneda_1' => ['nullable', 'integer', 'min:0'],
            'moneda_50_ctvs' => ['nullable', 'integer', 'min:0'],
            'moneda_20_ctvs' => ['nullable', 'integer', 'min:0'],
            'moneda_10_ctvs' => ['nullable', 'integer', 'min:0'],
        ];
    }
}