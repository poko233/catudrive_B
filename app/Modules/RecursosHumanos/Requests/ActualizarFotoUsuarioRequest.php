<?php

declare(strict_types=1);

namespace App\Modules\RecursosHumanos\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarFotoUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto' => [
                'bail',
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' =>
                'Debes seleccionar una fotografía.',

            'foto.file' =>
                'La fotografía enviada no es válida.',

            'foto.image' =>
                'El archivo debe ser una imagen válida.',

            'foto.mimes' =>
                'La fotografía debe ser JPG, JPEG, PNG o WebP.',

            'foto.max' =>
                'La fotografía no puede superar los 5 MB.',
        ];
    }
}
