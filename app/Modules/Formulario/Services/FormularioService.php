<?php

declare(strict_types=1);

namespace App\Modules\Formulario\Services;

use App\Modules\Auth\Services\SidebarCacheService;
use App\Shared\Models\Formulario;
use App\Shared\Services\AppCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FormularioService
{
    public function __construct(
        private readonly SidebarCacheService $sidebarCache,
        private readonly AppCacheService $cache
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listar formularios
    |--------------------------------------------------------------------------
    */

    public function listar(): Collection
    {
        /** @var Collection<int, Formulario> $formularios */
        $formularios =
            $this->cache
                ->remember(
                    AppCacheService::FORMULARIOS,

                    static fn (): Collection =>
                        Formulario::query()
                            ->with([
                                'modulos',
                            ])
                            ->orderBy(
                                'formulario'
                            )
                            ->get()
                );

        return $formularios;
    }

    /*
    |--------------------------------------------------------------------------
    | Crear formulario
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $data
    ): Formulario {
        $resultado =
            DB::transaction(
                function () use (
                    $data
                ): Formulario {
                    $formulario =
                        Formulario::query()
                            ->create([
                                'formulario' =>
                                    $data[
                                        'formulario'
                                    ],

                                'descripcion' =>
                                    $data[
                                        'descripcion'
                                    ]
                                    ?? null,

                                'ruta' =>
                                    $data[
                                        'ruta'
                                    ]
                                    ?? null,

                                'estado' =>
                                    $data[
                                        'estado'
                                    ]
                                    ?? 'Activo',
                            ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Módulos
                    |--------------------------------------------------------------------------
                    */

                    if (
                        array_key_exists(
                            'modulos',
                            $data
                        )
                    ) {
                        $formulario
                            ->modulos()
                            ->sync(
                                $this->normalizarIds(
                                    $data[
                                        'modulos'
                                    ]
                                    ?? []
                                )
                            );
                    }

                    return $formulario
                        ->fresh()
                        ->load(
                            'modulos'
                        );
                }
            );

        /*
         * Invalidamos después del COMMIT.
         */

        $this->invalidarCacheCatalogos();

        return $resultado;
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar formulario
    |--------------------------------------------------------------------------
    */

    public function actualizar(
        Formulario $formulario,
        array $data
    ): Formulario {
        /*
        |--------------------------------------------------------------------------
        | Roles antes
        |--------------------------------------------------------------------------
        */

        $rolesAntes =
            $this->rolesDeFormulario(
                (int)
                $formulario->id
            );

        /*
        |--------------------------------------------------------------------------
        | Transacción
        |--------------------------------------------------------------------------
        */

        $resultado =
            DB::transaction(
                function () use (
                    $formulario,
                    $data
                ): Formulario {
                    /*
                    |--------------------------------------------------------------------------
                    | Datos básicos
                    |--------------------------------------------------------------------------
                    */

                    $campos =
                        [];

                    foreach (
                        [
                            'formulario',
                            'descripcion',
                            'ruta',
                            'estado',
                        ]
                        as $campo
                    ) {
                        if (
                            array_key_exists(
                                $campo,
                                $data
                            )
                        ) {
                            $campos[
                                $campo
                            ] =
                                $data[
                                    $campo
                                ];
                        }
                    }

                    if (
                        $campos !==
                        []
                    ) {
                        $formulario
                            ->fill(
                                $campos
                            );

                        $formulario
                            ->save();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Módulos
                    |--------------------------------------------------------------------------
                    */

                    if (
                        array_key_exists(
                            'modulos',
                            $data
                        )
                    ) {
                        $modulos =
                            $this->normalizarIds(
                                $data[
                                    'modulos'
                                ]
                                ?? []
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Permisos del formulario
                        |--------------------------------------------------------------------------
                        */

                        $permisosQuery =
                            DB::table(
                                'formulario_permiso'
                            )
                                ->where(
                                    'id_formulario',
                                    $formulario->id
                                );

                        /*
                        |--------------------------------------------------------------------------
                        | Quitar todos los módulos
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $modulos ===
                            []
                        ) {
                            $rolesEliminados =
                                (
                                    clone
                                    $permisosQuery
                                )
                                    ->distinct()
                                    ->pluck(
                                        'id_rol'
                                    )
                                    ->map(
                                        fn (
                                            $id
                                        ) =>
                                            (int)
                                            $id
                                    )
                                    ->all();

                            $permisosQuery
                                ->delete();
                        } else {
                            /*
                            |--------------------------------------------------------------------------
                            | Roles que perderán permisos
                            |--------------------------------------------------------------------------
                            */

                            $rolesEliminados =
                                (
                                    clone
                                    $permisosQuery
                                )
                                    ->whereNotIn(
                                        'id_modulo',
                                        $modulos
                                    )
                                    ->distinct()
                                    ->pluck(
                                        'id_rol'
                                    )
                                    ->map(
                                        fn (
                                            $id
                                        ) =>
                                            (int)
                                            $id
                                    )
                                    ->all();

                            /*
                            |--------------------------------------------------------------------------
                            | Eliminar permisos inválidos
                            |--------------------------------------------------------------------------
                            */

                            $permisosQuery
                                ->whereNotIn(
                                    'id_modulo',
                                    $modulos
                                )
                                ->delete();
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Visibility huérfana
                        |--------------------------------------------------------------------------
                        */

                        foreach (
                            $rolesEliminados
                            as $idRol
                        ) {
                            $conservaPermiso =
                                DB::table(
                                    'formulario_permiso'
                                )
                                    ->where(
                                        'id_formulario',
                                        $formulario->id
                                    )
                                    ->where(
                                        'id_rol',
                                        $idRol
                                    )
                                    ->exists();

                            if (
                                $conservaPermiso
                            ) {
                                continue;
                            }

                            DB::table(
                                'formulario_accion'
                            )
                                ->where(
                                    'id_formulario',
                                    $formulario->id
                                )
                                ->where(
                                    'id_rol',
                                    $idRol
                                )
                                ->delete();
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | formulario_modulo
                        |--------------------------------------------------------------------------
                        */

                        $formulario
                            ->modulos()
                            ->sync(
                                $modulos
                            );
                    }

                    return $formulario
                        ->fresh()
                        ->load(
                            'modulos'
                        );
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Roles después
        |--------------------------------------------------------------------------
        */

        $rolesDespues =
            $this->rolesDeFormulario(
                (int)
                $formulario->id
            );

        $rolesAfectados =
            array_values(
                array_unique(
                    array_merge(
                        $rolesAntes,
                        $rolesDespues
                    )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Caché de roles
        |--------------------------------------------------------------------------
        */

        $this->invalidarRoles(
            $rolesAfectados
        );

        /*
        |--------------------------------------------------------------------------
        | Catálogos
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheCatalogos();

        return $resultado;
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar formulario
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    |
    | Se elimina:
    |
    | - formulario_permiso
    | - formulario_accion
    | - formulario_modulo
    | - formulario
    |
    | NO se elimina:
    |
    | - modulo
    | - rol
    | - accion
    |
    */

    public function eliminar(
        Formulario $formulario
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Guardar ID
        |--------------------------------------------------------------------------
        */

        $idFormulario =
            (int)
            $formulario->id;

        /*
        |--------------------------------------------------------------------------
        | Roles afectados
        |--------------------------------------------------------------------------
        |
        | Se obtienen ANTES de borrar permisos y Visibility.
        |
        */

        $rolesAfectados =
            $this->rolesDeFormulario(
                $idFormulario
            );

        /*
        |--------------------------------------------------------------------------
        | Transacción
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $formulario,
                $idFormulario
            ): void {
                /*
                |--------------------------------------------------------------------------
                | Visibility
                |--------------------------------------------------------------------------
                */

                DB::table(
                    'formulario_accion'
                )
                    ->where(
                        'id_formulario',
                        $idFormulario
                    )
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Permisos
                |--------------------------------------------------------------------------
                */

                DB::table(
                    'formulario_permiso'
                )
                    ->where(
                        'id_formulario',
                        $idFormulario
                    )
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Relaciones con módulos
                |--------------------------------------------------------------------------
                |
                | detach() elimina únicamente formulario_modulo.
                |
                | NO elimina módulos.
                |
                */

                $formulario
                    ->modulos()
                    ->detach();

                /*
                |--------------------------------------------------------------------------
                | Formulario
                |--------------------------------------------------------------------------
                */

                $formulario
                    ->delete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Roles afectados
        |--------------------------------------------------------------------------
        */

        $this->invalidarRoles(
            $rolesAfectados
        );

        /*
        |--------------------------------------------------------------------------
        | Catálogos
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheCatalogos();
    }

    /*
    |--------------------------------------------------------------------------
    | Roles relacionados con formulario
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, int>
     */
    private function rolesDeFormulario(
        int $idFormulario
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Permisos
        |--------------------------------------------------------------------------
        */

        $permisos =
            DB::table(
                'formulario_permiso'
            )
                ->where(
                    'id_formulario',
                    $idFormulario
                )
                ->pluck(
                    'id_rol'
                );

        /*
        |--------------------------------------------------------------------------
        | Visibility
        |--------------------------------------------------------------------------
        */

        $visibilidad =
            DB::table(
                'formulario_accion'
            )
                ->where(
                    'id_formulario',
                    $idFormulario
                )
                ->pluck(
                    'id_rol'
                );

        /*
        |--------------------------------------------------------------------------
        | Combinar
        |--------------------------------------------------------------------------
        */

        return $permisos
            ->merge(
                $visibilidad
            )
            ->map(
                fn (
                    $id
                ) =>
                    (int)
                    $id
            )
            ->unique()
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar roles
    |--------------------------------------------------------------------------
    */

    /**
     * @param array<int, int> $roles
     */
    private function invalidarRoles(
        array $roles
    ): void {
        if (
            $roles ===
            []
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Listados globales
        |--------------------------------------------------------------------------
        */

        $this->cache
            ->forgetRolesPermisos();

        $this->cache
            ->forgetFormularioAcciones();

        /*
        |--------------------------------------------------------------------------
        | Cada rol
        |--------------------------------------------------------------------------
        */

        foreach (
            $roles
            as $idRol
        ) {
            $this
                ->sidebarCache
                ->forgetRol(
                    (int)
                    $idRol
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar catálogos
    |--------------------------------------------------------------------------
    */

    private function invalidarCacheCatalogos(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Formularios
        |--------------------------------------------------------------------------
        */

        $this->cache
            ->forgetFormularios();

        /*
        |--------------------------------------------------------------------------
        | Módulos
        |--------------------------------------------------------------------------
        |
        | El catálogo de módulos carga:
        |
        | Modulo::with('formularios')
        |
        */

        $this->cache
            ->forgetModulos();

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $this->cache
            ->forgetRoles();
    }

    /*
    |--------------------------------------------------------------------------
    | Normalizar IDs
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, int>
     */
    private function normalizarIds(
        array $ids
    ): array {
        return array_values(
            array_unique(
                array_map(
                    'intval',
                    $ids
                )
            )
        );
    }
}