<?php

declare(strict_types=1);

namespace App\Modules\Empresa\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImagenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagen' => [
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
            'imagen.required' => 'Debe seleccionar una imagen.',
            'imagen.file' => 'Debe seleccionar un archivo válido.',
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WebP.',
            'imagen.max' => 'La imagen no puede superar los 5 MB.',
        ];
    }
}
