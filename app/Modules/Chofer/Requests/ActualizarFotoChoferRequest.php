<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarFotoChoferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}