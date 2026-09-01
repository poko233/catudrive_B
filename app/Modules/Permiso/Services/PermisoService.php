<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Services;

use App\Modules\Auth\Services\SidebarCacheService;
use App\Shared\Models\FormularioPermiso;
use App\Shared\Models\Rol;
use App\Shared\Services\AppCacheService;
use App\Shared\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermisoService
{
    public function __construct(
        private readonly SidebarCacheService $sidebarCache,
        private readonly AuditService $audit,
        private readonly AppCacheService $cache
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Permisos de un rol
    |--------------------------------------------------------------------------
    |
    | Cache independiente por rol:
    |
    | metasoft:rol:1:permisos-admin
    | metasoft:rol:2:permisos-admin
    |
    */

    public function getPermisosByRol(
        int $idRol
    ): Collection {
        /*
        |--------------------------------------------------------------------------
        | Validar rol
        |--------------------------------------------------------------------------
        */

        Rol::query()
            ->findOrFail(
                $idRol
            );

        /*
        |--------------------------------------------------------------------------
        | Obtener permisos
        |--------------------------------------------------------------------------
        */

        /** @var Collection<int, FormularioPermiso> $permisos */
        $permisos =
            $this->cache
                ->rememberForRole(
                    $idRol,
                    'permisos-admin',
                    fn (): Collection =>
                        FormularioPermiso::query()
                            ->with([
                                'rol:id,rol',
                                'modulo:id,modulo',
                                'formulario:id,formulario',
                                'accion:id,accion',
                            ])
                            ->where(
                                'id_rol',
                                $idRol
                            )
                            ->orderBy(
                                'id_modulo'
                            )
                            ->orderBy(
                                'id_formulario'
                            )
                            ->orderBy(
                                'id_accion'
                            )
                            ->get()
                );

        return $permisos;
    }

    /*
    |--------------------------------------------------------------------------
    | Agregar permiso individual
    |--------------------------------------------------------------------------
    */

    public function addPermiso(
        int $idRol,
        array $permiso
    ): FormularioPermiso {
        /*
        |--------------------------------------------------------------------------
        | Validar rol
        |--------------------------------------------------------------------------
        */

        Rol::query()
            ->findOrFail(
                $idRol
            );

        $idModulo =
            (int)
            $permiso['id_modulo'];

        $idFormulario =
            (int)
            $permiso['id_formulario'];

        $idAccion =
            (int)
            $permiso['id_accion'];

        /*
        |--------------------------------------------------------------------------
        | Verificar que el módulo esté asignado al rol
        |--------------------------------------------------------------------------
        */

        $this->validateRolModulo(
            $idRol,
            $idModulo
        );

        /*
        |--------------------------------------------------------------------------
        | Validar módulo / formulario
        |--------------------------------------------------------------------------
        */

        $this->validateModuloFormulario(
            $idModulo,
            $idFormulario
        );

        /*
        |--------------------------------------------------------------------------
        | Evitar duplicados
        |--------------------------------------------------------------------------
        */

        $exists =
            FormularioPermiso::query()
                ->where(
                    'id_rol',
                    $idRol
                )
                ->where(
                    'id_modulo',
                    $idModulo
                )
                ->where(
                    'id_formulario',
                    $idFormulario
                )
                ->where(
                    'id_accion',
                    $idAccion
                )
                ->exists();

        if (
            $exists
        ) {
            throw ValidationException::withMessages([
                'id_accion' => [
                    'El rol ya posee este permiso.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Crear
        |--------------------------------------------------------------------------
        */

        $nuevoPermiso =
            DB::transaction(
                function () use (
                    $idRol,
                    $idModulo,
                    $idFormulario,
                    $idAccion
                ): FormularioPermiso {
                    return FormularioPermiso::query()
                        ->create([
                            'id_rol' =>
                                $idRol,

                            'id_modulo' =>
                                $idModulo,

                            'id_formulario' =>
                                $idFormulario,

                            'id_accion' =>
                                $idAccion,
                        ]);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheRol(
            $idRol
        );

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        $this->audit->created(
            resource:
                'FormularioPermiso',

            resourceId:
                $nuevoPermiso->id,

            after:
                $nuevoPermiso->toArray(),
        );

        return $nuevoPermiso->load([
            'rol:id,rol',
            'modulo:id,modulo',
            'formulario:id,formulario',
            'accion:id,accion',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar todos los permisos
    |--------------------------------------------------------------------------
    */

    public function syncPermisos(
        int $idRol,
        array $permisos
    ): Collection {
        /*
        |--------------------------------------------------------------------------
        | Validar rol
        |--------------------------------------------------------------------------
        */

        Rol::query()
            ->findOrFail(
                $idRol
            );

        /*
        |--------------------------------------------------------------------------
        | Normalizar
        |--------------------------------------------------------------------------
        */

        $normalizados =
            $this->normalizarPermisos(
                $permisos
            );

        /*
        |--------------------------------------------------------------------------
        | Validar permisos
        |--------------------------------------------------------------------------
        */

        foreach (
            $normalizados
            as $permiso
        ) {
            $this->validateRolModulo(
                $idRol,
                $permiso['id_modulo']
            );

            $this->validateModuloFormulario(
                $permiso['id_modulo'],
                $permiso['id_formulario']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Estado anterior
        |--------------------------------------------------------------------------
        */

        $before =
            FormularioPermiso::query()
                ->where(
                    'id_rol',
                    $idRol
                )
                ->get()
                ->map(
                    fn (
                        FormularioPermiso $permiso
                    ) =>
                        $permiso->only([
                            'id',
                            'id_rol',
                            'id_modulo',
                            'id_formulario',
                            'id_accion',
                        ])
                )
                ->values()
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Sincronización
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $idRol,
                $normalizados
            ): void {
                /*
                |--------------------------------------------------------------------------
                | Eliminar permisos anteriores
                |--------------------------------------------------------------------------
                */

                FormularioPermiso::query()
                    ->where(
                        'id_rol',
                        $idRol
                    )
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Insertar nuevos
                |--------------------------------------------------------------------------
                */

                if (
                    $normalizados !== []
                ) {
                    $now =
                        now();

                    $rows =
                        array_map(
                            static fn (
                                array $permiso
                            ) => [
                                'id_rol' =>
                                    $idRol,

                                'id_modulo' =>
                                    $permiso[
                                        'id_modulo'
                                    ],

                                'id_formulario' =>
                                    $permiso[
                                        'id_formulario'
                                    ],

                                'id_accion' =>
                                    $permiso[
                                        'id_accion'
                                    ],

                                'created_at' =>
                                    $now,

                                'updated_at' =>
                                    $now,
                            ],

                            $normalizados
                        );

                    FormularioPermiso::query()
                        ->insert(
                            $rows
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Formularios que todavía tienen acceso
                |--------------------------------------------------------------------------
                */

                $formulariosConAcceso =
                    array_values(
                        array_unique(
                            array_column(
                                $normalizados,
                                'id_formulario'
                            )
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Limpiar Visibility huérfana
                |--------------------------------------------------------------------------
                */

                $visibilityQuery =
                    DB::table(
                        'formulario_accion'
                    )
                        ->where(
                            'id_rol',
                            $idRol
                        );

                if (
                    $formulariosConAcceso === []
                ) {
                    $visibilityQuery
                        ->delete();
                } else {
                    $visibilityQuery
                        ->whereNotIn(
                            'id_formulario',
                            $formulariosConAcceso
                        )
                        ->delete();
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        |
        | Esta operación sí puede eliminar formulario_accion.
        |
        */

        $this->invalidarCacheRol(
            $idRol,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Estado posterior
        |--------------------------------------------------------------------------
        */

        $after =
            FormularioPermiso::query()
                ->where(
                    'id_rol',
                    $idRol
                )
                ->get()
                ->map(
                    fn (
                        FormularioPermiso $permiso
                    ) =>
                        $permiso->only([
                            'id',
                            'id_rol',
                            'id_modulo',
                            'id_formulario',
                            'id_accion',
                        ])
                )
                ->values()
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        $this->audit->log(
            action:
                'Editar',

            resource:
                'PermisosRol',

            resourceId:
                $idRol,

            before: [
                'permisos' =>
                    $before,
            ],

            after: [
                'permisos' =>
                    $after,
            ],
        );

        return $this->getPermisosByRol(
            $idRol
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar permiso
    |--------------------------------------------------------------------------
    */

    public function removeByParams(
        int $idRol,
        int $idFormulario,
        int $idAccion
    ): int {
        /*
        |--------------------------------------------------------------------------
        | Validar rol
        |--------------------------------------------------------------------------
        */

        Rol::query()
            ->findOrFail(
                $idRol
            );

        /*
        |--------------------------------------------------------------------------
        | Estado anterior
        |--------------------------------------------------------------------------
        */

        $before =
            FormularioPermiso::query()
                ->where(
                    'id_rol',
                    $idRol
                )
                ->where(
                    'id_formulario',
                    $idFormulario
                )
                ->where(
                    'id_accion',
                    $idAccion
                )
                ->get()
                ->map(
                    fn (
                        FormularioPermiso $permiso
                    ) =>
                        $permiso->only([
                            'id',
                            'id_rol',
                            'id_modulo',
                            'id_formulario',
                            'id_accion',
                        ])
                )
                ->values()
                ->all();

        if (
            $before === []
        ) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Saber si Visibility fue eliminada
        |--------------------------------------------------------------------------
        */

        $visibilityEliminada =
            false;

        /*
        |--------------------------------------------------------------------------
        | Eliminar
        |--------------------------------------------------------------------------
        */

        $eliminados =
            DB::transaction(
                function () use (
                    $idRol,
                    $idFormulario,
                    $idAccion,
                    &$visibilityEliminada
                ): int {
                    $deleted =
                        FormularioPermiso::query()
                            ->where(
                                'id_rol',
                                $idRol
                            )
                            ->where(
                                'id_formulario',
                                $idFormulario
                            )
                            ->where(
                                'id_accion',
                                $idAccion
                            )
                            ->delete();

                    /*
                    |--------------------------------------------------------------------------
                    | Verificar acceso restante
                    |--------------------------------------------------------------------------
                    */

                    $conservaAcceso =
                        FormularioPermiso::query()
                            ->where(
                                'id_rol',
                                $idRol
                            )
                            ->where(
                                'id_formulario',
                                $idFormulario
                            )
                            ->exists();

                    /*
                    |--------------------------------------------------------------------------
                    | Limpiar Visibility
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$conservaAcceso
                    ) {
                        $visibilityEliminada =
                            DB::table(
                                'formulario_accion'
                            )
                                ->where(
                                    'id_rol',
                                    $idRol
                                )
                                ->where(
                                    'id_formulario',
                                    $idFormulario
                                )
                                ->delete()
                            > 0;
                    }

                    return $deleted;
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheRol(
            $idRol,
            $visibilityEliminada
        );

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        $this->audit->log(
            action:
                'Eliminar',

            resource:
                'FormularioPermiso',

            resourceId:
                null,

            before: [
                'permisos' =>
                    $before,
            ],

            after:
                [],

            context: [
                'id_rol' =>
                    $idRol,

                'id_formulario' =>
                    $idFormulario,

                'id_accion' =>
                    $idAccion,
            ],
        );

        return $eliminados;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar módulo asignado al rol
    |--------------------------------------------------------------------------
    |
    | Evita otorgar permisos sobre un módulo que el rol no tiene
    | asignado mediante modulo_rol.
    |
    */

    private function validateRolModulo(
        int $idRol,
        int $idModulo
    ): void {
        $existe =
            DB::table(
                'modulo_rol'
            )
                ->where(
                    'id_rol',
                    $idRol
                )
                ->where(
                    'id_modulo',
                    $idModulo
                )
                ->exists();

        if (
            !$existe
        ) {
            throw ValidationException::withMessages([
                'id_modulo' => [
                    'El módulo debe estar asignado al rol antes de otorgar permisos.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validar formulario del módulo
    |--------------------------------------------------------------------------
    */

    private function validateModuloFormulario(
        int $idModulo,
        int $idFormulario
    ): void {
        $relacionExiste =
            DB::table(
                'formulario_modulo'
            )
                ->where(
                    'id_modulo',
                    $idModulo
                )
                ->where(
                    'id_formulario',
                    $idFormulario
                )
                ->exists();

        if (
            !$relacionExiste
        ) {
            throw ValidationException::withMessages([
                'id_formulario' => [
                    'El formulario seleccionado no pertenece al módulo indicado.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar caches del rol
    |--------------------------------------------------------------------------
    */

    private function invalidarCacheRol(
        int $idRol,
        bool $visibilityPuedeCambiar = false
    ): void {
        /*
         * Auth:
         *
         * - permisos de usuarios del rol
         * - sidebar del rol
         * - Visibility del rol
         */

        $this->sidebarCache
            ->forgetRol(
                $idRol
            );

        /*
         * GET específico:
         *
         * /permisos/{rol}
         */

        $this->cache
            ->forgetForRole(
                $idRol,
                'permisos-admin'
            );

        /*
         * RolService::detalle()
         */

        $this->cache
            ->forgetForRole(
                $idRol,
                'detalle'
            );

        /*
         * GET /api/roles/permisos
         */

        $this->cache
            ->forgetRolesPermisos();

        /*
         * Si un permiso perdió todo acceso a un formulario,
         * pudo haberse eliminado formulario_accion.
         */

        if (
            $visibilityPuedeCambiar
        ) {
            $this->cache
                ->forgetFormularioAcciones();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Normalizar permisos
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, array{
     *     id_modulo:int,
     *     id_formulario:int,
     *     id_accion:int
     * }>
     */
    private function normalizarPermisos(
        array $permisos
    ): array {
        $resultado = [];

        $vistos = [];

        foreach (
            $permisos
            as $permiso
        ) {
            $normalizado = [
                'id_modulo' =>
                    (int)
                    $permiso[
                        'id_modulo'
                    ],

                'id_formulario' =>
                    (int)
                    $permiso[
                        'id_formulario'
                    ],

                'id_accion' =>
                    (int)
                    $permiso[
                        'id_accion'
                    ],
            ];

            $key =
                $normalizado[
                    'id_modulo'
                ]
                . ':'
                . $normalizado[
                    'id_formulario'
                ]
                . ':'
                . $normalizado[
                    'id_accion'
                ];

            if (
                isset(
                    $vistos[
                        $key
                    ]
                )
            ) {
                continue;
            }

            $vistos[$key] =
                true;

            $resultado[] =
                $normalizado;
        }

        return $resultado;
    }
}