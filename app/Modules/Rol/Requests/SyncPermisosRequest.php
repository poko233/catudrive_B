<?php

declare(strict_types=1);

namespace App\Modules\Rol\Requests;

use App\Shared\Models\Accion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class SyncPermisosRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Autorización
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Reglas base
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return [
            /*
             * [] significa quitar todos los permisos.
             */
            'permisos' => [
                'present',
                'array',
            ],

            'permisos.*.id_modulo' => [
                'required',
                'integer',
            ],

            'permisos.*.id_formulario' => [
                'required',
                'integer',
            ],

            'permisos.*.acciones' => [
                'required',
                'array',
                'min:1',
            ],

            /*
             * IMPORTANTE:
             *
             * No usamos "distinct" aquí.
             *
             * Una misma acción puede aparecer
             * correctamente en distintos permisos.
             *
             * Ejemplo:
             *
             * Formulario A -> Ver, Editar
             * Formulario B -> Ver, Editar
             *
             * La validación de duplicados se realiza
             * después, permiso por permiso.
             */
            'permisos.*.acciones.*' => [
                'required',
                'integer',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Mensajes
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [
            'permisos.present' =>
                'Debes enviar el campo permisos, aunque sea vacío [].',

            'permisos.array' =>
                'Los permisos deben ser un arreglo.',

            'permisos.*.id_modulo.required' =>
                'Cada permiso requiere id_modulo.',

            'permisos.*.id_modulo.integer' =>
                'El módulo seleccionado no es válido.',

            'permisos.*.id_formulario.required' =>
                'Cada permiso requiere id_formulario.',

            'permisos.*.id_formulario.integer' =>
                'El formulario seleccionado no es válido.',

            'permisos.*.acciones.required' =>
                'Cada permiso requiere al menos una acción.',

            'permisos.*.acciones.array' =>
                'Las acciones deben enviarse como un arreglo.',

            'permisos.*.acciones.min' =>
                'Cada permiso requiere al menos una acción.',

            'permisos.*.acciones.*.integer' =>
                'Una de las acciones enviadas no es válida.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones adicionales
    |--------------------------------------------------------------------------
    */

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ): void {
                $permisos =
                    $this->input(
                        'permisos',
                        []
                    );

                /*
                |--------------------------------------------------------------------------
                | Sin permisos
                |--------------------------------------------------------------------------
                */

                if (
                    !is_array($permisos) ||
                    $permisos === []
                ) {
                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Recolectar IDs
                |--------------------------------------------------------------------------
                */

                $moduloIds = [];

                $formularioIds = [];

                $accionIds = [];

                foreach (
                    $permisos as $permiso
                ) {
                    if (
                        !is_array($permiso)
                    ) {
                        continue;
                    }

                    if (
                        isset(
                            $permiso[
                                'id_modulo'
                            ]
                        )
                    ) {
                        $moduloIds[] =
                            (int)
                            $permiso[
                                'id_modulo'
                            ];
                    }

                    if (
                        isset(
                            $permiso[
                                'id_formulario'
                            ]
                        )
                    ) {
                        $formularioIds[] =
                            (int)
                            $permiso[
                                'id_formulario'
                            ];
                    }

                    $acciones =
                        $permiso[
                            'acciones'
                        ]
                        ?? [];

                    if (
                        !is_array(
                            $acciones
                        )
                    ) {
                        continue;
                    }

                    foreach (
                        $acciones
                        as $idAccion
                    ) {
                        $accionIds[] =
                            (int)
                            $idAccion;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Normalizar IDs
                |--------------------------------------------------------------------------
                */

                $moduloIds =
                    array_values(
                        array_unique(
                            $moduloIds
                        )
                    );

                $formularioIds =
                    array_values(
                        array_unique(
                            $formularioIds
                        )
                    );

                $accionIds =
                    array_values(
                        array_unique(
                            $accionIds
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Módulos existentes
                |--------------------------------------------------------------------------
                */

                $modulosExistentes =
                    $moduloIds === []
                        ? []
                        : DB::table(
                            'modulo'
                        )
                            ->whereIn(
                                'id',
                                $moduloIds
                            )
                            ->pluck(
                                'id'
                            )
                            ->map(
                                static fn (
                                    $id
                                ) =>
                                    (int)
                                    $id
                            )
                            ->all();

                /*
                |--------------------------------------------------------------------------
                | Formularios existentes
                |--------------------------------------------------------------------------
                */

                $formulariosExistentes =
                    $formularioIds === []
                        ? []
                        : DB::table(
                            'formulario'
                        )
                            ->whereIn(
                                'id',
                                $formularioIds
                            )
                            ->pluck(
                                'id'
                            )
                            ->map(
                                static fn (
                                    $id
                                ) =>
                                    (int)
                                    $id
                            )
                            ->all();

                /*
                |--------------------------------------------------------------------------
                | Acciones existentes
                |--------------------------------------------------------------------------
                */

                $accionesExistentes =
                    $accionIds === []
                        ? []
                        : DB::table(
                            'accion'
                        )
                            ->whereIn(
                                'id',
                                $accionIds
                            )
                            ->pluck(
                                'id'
                            )
                            ->map(
                                static fn (
                                    $id
                                ) =>
                                    (int)
                                    $id
                            )
                            ->all();

                /*
                |--------------------------------------------------------------------------
                | Catálogo oficial de acciones
                |--------------------------------------------------------------------------
                */

                $accionesOficiales =
                    Accion::ids();

                /*
                |--------------------------------------------------------------------------
                | Relaciones formulario ↔ módulo
                |--------------------------------------------------------------------------
                */

                $relaciones = [];

                if (
                    $moduloIds !== [] &&
                    $formularioIds !== []
                ) {
                    $relaciones =
                        DB::table(
                            'formulario_modulo'
                        )
                            ->whereIn(
                                'id_modulo',
                                $moduloIds
                            )
                            ->whereIn(
                                'id_formulario',
                                $formularioIds
                            )
                            ->get([
                                'id_modulo',
                                'id_formulario',
                            ])
                            ->mapWithKeys(
                                static fn (
                                    $row
                                ) => [
                                    (int)
                                    $row
                                        ->id_modulo
                                    . ':'
                                    . (int)
                                    $row
                                        ->id_formulario
                                        => true,
                                ]
                            )
                            ->all();
                }

                /*
                |--------------------------------------------------------------------------
                | Validar cada permiso
                |--------------------------------------------------------------------------
                */

                foreach (
                    $permisos
                    as $indice =>
                        $permiso
                ) {
                    if (
                        !is_array(
                            $permiso
                        )
                    ) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Necesitamos módulo y formulario
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset(
                            $permiso[
                                'id_modulo'
                            ],
                            $permiso[
                                'id_formulario'
                            ]
                        )
                    ) {
                        continue;
                    }

                    $idModulo =
                        (int)
                        $permiso[
                            'id_modulo'
                        ];

                    $idFormulario =
                        (int)
                        $permiso[
                            'id_formulario'
                        ];

                    /*
                    |--------------------------------------------------------------------------
                    | Módulo existente
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !in_array(
                            $idModulo,
                            $modulosExistentes,
                            true
                        )
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "permisos.{$indice}.id_modulo",
                                'Uno de los módulos enviados no existe.'
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Formulario existente
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !in_array(
                            $idFormulario,
                            $formulariosExistentes,
                            true
                        )
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "permisos.{$indice}.id_formulario",
                                'Uno de los formularios enviados no existe.'
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Formulario pertenece al módulo
                    |--------------------------------------------------------------------------
                    */

                    $key =
                        $idModulo
                        . ':'
                        . $idFormulario;

                    if (
                        in_array(
                            $idModulo,
                            $modulosExistentes,
                            true
                        ) &&
                        in_array(
                            $idFormulario,
                            $formulariosExistentes,
                            true
                        ) &&
                        !isset(
                            $relaciones[
                                $key
                            ]
                        )
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "permisos.{$indice}.id_formulario",
                                'El formulario no pertenece al módulo indicado.'
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Acciones
                    |--------------------------------------------------------------------------
                    */

                    $acciones =
                        $permiso[
                            'acciones'
                        ]
                        ?? [];

                    if (
                        !is_array(
                            $acciones
                        )
                    ) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Acciones duplicadas DENTRO del mismo permiso
                    |--------------------------------------------------------------------------
                    |
                    | Esto sí es inválido:
                    |
                    | acciones: [1, 1, 3]
                    |
                    | Pero esto es totalmente válido:
                    |
                    | Formulario A -> [1, 3]
                    | Formulario B -> [1, 3]
                    |
                    */

                    $accionesNormalizadas =
                        array_map(
                            'intval',
                            $acciones
                        );

                    if (
                        count(
                            $accionesNormalizadas
                        )
                        !==
                        count(
                            array_unique(
                                $accionesNormalizadas
                            )
                        )
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "permisos.{$indice}.acciones",
                                'No se puede repetir una misma acción dentro del permiso.'
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Validar acciones existentes/oficiales
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $acciones
                        as $j =>
                            $idAccion
                    ) {
                        $idAccion =
                            (int)
                            $idAccion;

                        if (
                            !in_array(
                                $idAccion,
                                $accionesExistentes,
                                true
                            ) ||
                            !in_array(
                                $idAccion,
                                $accionesOficiales,
                                true
                            )
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    "permisos.{$indice}.acciones.{$j}",
                                    'Una de las acciones enviadas no es válida.'
                                );
                        }
                    }
                }
            },
        ];
    }
}