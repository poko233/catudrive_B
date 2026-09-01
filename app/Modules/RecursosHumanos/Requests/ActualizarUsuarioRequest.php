<?php

declare(strict_types=1);

namespace App\Modules\RecursosHumanos\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizados = [];

        $stringsObligatorios = [
            'usuario',
            'ci',
            'nombres',
            'apellidoPaterno',
            'estado',
        ];

        foreach ($stringsObligatorios as $campo) {
            if (!$this->exists($campo)) {
                continue;
            }

            $normalizados[$campo] =
                trim((string) $this->input($campo));
        }

        $stringsNullable = [
            'apellidoMaterno',
            'fecha_nac',
            'telefono',
            'celular',
            'direccion',
            'expedido',
            'referenciaNombre',
            'referenciaParentesco',
            'referenciaNumero',
        ];

        foreach ($stringsNullable as $campo) {
            if (!$this->exists($campo)) {
                continue;
            }

            $normalizados[$campo] =
                $this->nullableString(
                    $this->input($campo)
                );
        }

        if ($this->exists('email')) {
            $email =
                $this->nullableString(
                    $this->input('email')
                );

            $normalizados['email'] =
                $email !== null
                    ? mb_strtolower($email)
                    : null;
        }

        if ($this->exists('genero')) {
            $genero =
                $this->nullableString(
                    $this->input('genero')
                );

            $normalizados['genero'] =
                $genero !== null
                    ? mb_strtoupper($genero)
                    : null;
        }

        if ($this->exists('estado')) {
            $normalizados['estado'] =
                mb_strtoupper(
                    trim(
                        (string)
                        $this->input('estado')
                    )
                );
        }

        if ($normalizados !== []) {
            $this->merge(
                $normalizados
            );
        }
    }

    public function rules(): array
    {
        $id =
            (int) $this->route('id');

        $editingPartial =
            $this->isMethod('PATCH');

        return [
            'usuario' => [
                $editingPartial
                    ? 'sometimes'
                    : 'required',

                'required',
                'string',
                'max:40',

                Rule::unique(
                    'user',
                    'usuario'
                )->ignore(
                    $id,
                    'id'
                ),
            ],

            'ci' => [
                $editingPartial
                    ? 'sometimes'
                    : 'required',

                'required',
                'string',
                'max:12',

                Rule::unique(
                    'user',
                    'ci'
                )->ignore(
                    $id,
                    'id'
                ),
            ],

            'nombres' => [
                $editingPartial
                    ? 'sometimes'
                    : 'required',

                'required',
                'string',
                'max:40',
            ],

            'apellidoPaterno' => [
                $editingPartial
                    ? 'sometimes'
                    : 'required',

                'required',
                'string',
                'max:50',
            ],

            'apellidoMaterno' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'genero' => [
                'sometimes',
                'nullable',

                Rule::in([
                    'MASCULINO',
                    'FEMENINO',
                    'OTRO',
                ]),
            ],

            'fecha_nac' => [
                'sometimes',
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email:rfc',
                'max:80',

                Rule::unique(
                    'user',
                    'email'
                )->ignore(
                    $id,
                    'id'
                ),
            ],

            'telefono' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],

            'celular' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'direccion' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'expedido' => [
                'sometimes',
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

            'estado' => [
                $editingPartial
                    ? 'sometimes'
                    : 'required',

                'required',

                Rule::in([
                    'ACTIVO',
                    'INACTIVO',
                ]),
            ],

            /*
             * Compatibilidad con el formulario actual.
             *
             * La BD PostgreSQL actual no tiene NumeroReferencia,
             * por lo que estos campos se validan pero no se persisten.
             */
            'referenciaNombre' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'referenciaParentesco' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'referenciaNumero' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' =>
                'El usuario es obligatorio.',

            'usuario.unique' =>
                'Ese usuario ya está registrado.',

            'ci.required' =>
                'El carnet de identidad es obligatorio.',

            'ci.unique' =>
                'Ese carnet de identidad ya está registrado.',

            'nombres.required' =>
                'Los nombres son obligatorios.',

            'apellidoPaterno.required' =>
                'El primer apellido es obligatorio.',

            'genero.in' =>
                'El género seleccionado no es válido.',

            'fecha_nac.date' =>
                'La fecha de nacimiento no es válida.',

            'fecha_nac.before_or_equal' =>
                'La fecha de nacimiento no puede ser posterior a hoy.',

            'email.email' =>
                'El correo electrónico no es válido.',

            'email.unique' =>
                'Ese correo electrónico ya está registrado.',

            'expedido.in' =>
                'El lugar de expedición no es válido.',

            'estado.required' =>
                'El estado es obligatorio.',

            'estado.in' =>
                'El estado seleccionado no es válido.',
        ];
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value === ''
            ? null
            : $value;
    }
}
