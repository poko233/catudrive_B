<?php

declare(strict_types=1);

namespace App\Modules\Encomienda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncomiendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_ruta' => [
                'required',
                'integer',
                'exists:ruta,id',
            ],

            'remitente' => [
                'required',
                'string',
                'max:255',
            ],

            'destinatario' => [
                'required',
                'string',
                'max:255',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'cantidad' => [
                'required',
                'integer',
                'min:1',
            ],

            'precio' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_ruta.required' =>
                'Debe seleccionar una ruta.',

            'id_ruta.integer' =>
                'La ruta seleccionada no es válida.',

            'id_ruta.exists' =>
                'La ruta seleccionada no existe.',

            'remitente.required' =>
                'El remitente es obligatorio.',

            'remitente.string' =>
                'El remitente no es válido.',

            'remitente.max' =>
                'El remitente no puede superar los 255 caracteres.',

            'destinatario.required' =>
                'El destinatario es obligatorio.',

            'destinatario.string' =>
                'El destinatario no es válido.',

            'destinatario.max' =>
                'El destinatario no puede superar los 255 caracteres.',

            'descripcion.string' =>
                'La descripción no es válida.',

            'cantidad.required' =>
                'La cantidad es obligatoria.',

            'cantidad.integer' =>
                'La cantidad debe ser un número entero.',

            'cantidad.min' =>
                'La cantidad debe ser mayor o igual a 1.',

            'precio.required' =>
                'El precio es obligatorio.',

            'precio.numeric' =>
                'El precio debe ser numérico.',

            'precio.min' =>
                'El precio no puede ser negativo.',

            'precio.decimal' =>
                'El precio puede tener como máximo 2 decimales.',
        ];
    }
}