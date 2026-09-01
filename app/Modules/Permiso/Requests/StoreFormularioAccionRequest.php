<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormularioAccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('selector_html')) {
            $this->merge([
                'selector_html' =>
                    trim((string) $this->input('selector_html')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'id_rol' => [
                'required',
                'integer',
                'exists:rol,id',
            ],

            'id_formulario' => [
                'required',
                'integer',
                'exists:formulario,id',
            ],

            'selector_html' => [
                'required',
                'string',
                'max:200',

                Rule::unique(
                    'formulario_accion',
                    'selector_html'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'id_rol',
                                $this->integer('id_rol')
                            )
                            ->where(
                                'id_formulario',
                                $this->integer('id_formulario')
                            )
                ),
            ],

            'habilitado' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_rol.required' =>
                'El rol es obligatorio.',

            'id_rol.exists' =>
                'El rol seleccionado no existe.',

            'id_formulario.required' =>
                'El formulario es obligatorio.',

            'id_formulario.exists' =>
                'El formulario seleccionado no existe.',

            'selector_html.required' =>
                'El selector es obligatorio.',

            'selector_html.max' =>
                'El selector no puede superar los 200 caracteres.',

            'selector_html.unique' =>
                'Esta regla de visibilidad ya existe para el rol y formulario.',
        ];
    }
}
