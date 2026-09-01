<?php

declare(strict_types=1);

namespace App\Modules\Empresa\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    protected function prepareForValidation(): void
    {
        $campos = [
            'empresa',
            'slogan',
            'sigla',
            'telefono',
            'celular',
            'email',
            'direccion',
            'responsable',
            'objeto',
            'mision',
            'vision',
            'estado',
            'facebook',
            'instagram',
            'tiktok',
            'linkedin',
            'carrito',
            'titulo_cierre',
            'mensaje_cierre',
            'titulo_inicio',
            'mensaje_inicio',
            'dominio',
            'smtp_correo',
            'correo_institucional',
        ];

        $normalizados = [];

        foreach ($campos as $campo) {
            if (!$this->exists($campo)) {
                continue;
            }

            $valor = $this->input($campo);

            if (!is_string($valor)) {
                continue;
            }

            $valor = trim($valor);

            $normalizados[$campo] =
                $valor === ''
                    ? null
                    : $valor;
        }

        if ($normalizados !== []) {
            $this->merge($normalizados);
        }
    }


    public function rules(): array
    {
        return [
            'empresa' => ['sometimes', 'required', 'string', 'max:100'],
            'slogan' => ['sometimes', 'nullable', 'string'],
            'sigla' => ['sometimes', 'nullable', 'string', 'max:200'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:11'],
            'celular' => ['sometimes', 'nullable', 'string', 'max:11'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:80'],
            'direccion' => ['sometimes', 'nullable', 'string'],
            'responsable' => ['sometimes', 'nullable', 'string', 'max:80'],
            'latitud' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'objeto' => ['sometimes', 'nullable', 'string'],
            'mision' => ['sometimes', 'nullable', 'string'],
            'vision' => ['sometimes', 'nullable', 'string'],
            'estado' => ['sometimes', 'in:Activo,Inactivo'],
            'facebook' => ['sometimes', 'nullable', 'string', 'max:40'],
            'instagram' => ['sometimes', 'nullable', 'string', 'max:40'],
            'tiktok' => ['sometimes', 'nullable', 'string', 'max:40'],
            'linkedin' => ['sometimes', 'nullable', 'string', 'max:40'],
            'carrito' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tipo_cambio' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
            'titulo_cierre' => ['sometimes', 'nullable', 'string', 'max:80'],
            'mensaje_cierre' => ['sometimes', 'nullable', 'string'],
            'titulo_inicio' => ['sometimes', 'nullable', 'string', 'max:80'],
            'mensaje_inicio' => ['sometimes', 'nullable', 'string'],
            'dominio' => ['sometimes', 'nullable', 'string', 'max:200'],
            'smtp_correo' => ['sometimes', 'nullable', 'string', 'max:100'],
            'correo_institucional' => ['sometimes', 'nullable', 'email:rfc', 'max:80'],
            'pwd_institucional' => ['sometimes', 'nullable', 'string', 'max:80'],
        ];
    }
}
