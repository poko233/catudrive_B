<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
{
    /**
     * La autorización real la resuelve el middleware `permiso:...`.
     * Este FormRequest solo valida la forma del payload.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'usuario' => [
                'required',
                'string',
                'max:40',
                Rule::unique('user', 'usuario'),
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
            'ci' => [
                'required',
                'string',
                'max:12',
                Rule::unique('user', 'ci'),
            ],
            'nombres' => [
                'required',
                'string',
                'max:40',
            ],
            'apellidoPaterno' => [
                'required',
                'string',
                'max:50',
            ],
            'apellidoMaterno' => [
                'nullable',
                'string',
                'max:50',
            ],
            'genero' => [
                'required',
                Rule::in(['MASCULINO', 'FEMENINO']),
            ],
            'fecha_nac' => [
                'required',
                'date',
                'before:today',
                'after:' . now()->subYears(150)->format('Y-m-d'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:80',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:10',
            ],
            'celular' => [
                'nullable',
                'string',
                'max:20',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:50',
            ],
            'expedido' => [
                'nullable',
                Rule::in([
                    'LPZ',
                    'CBBA',
                    'OR',
                    'PT',
                    'TJ',
                    'SCZ',
                    'BN',
                    'PD',
                    'CH',
                    'QR',
                    'EXT',
                ]),
            ],
            'roles' => [
                'required',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'string',
                Rule::exists('rol', 'rol'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Debe seleccionar al menos un rol.',
            'roles.*.exists' => 'El rol seleccionado no es válido.',
        ];
    }
}