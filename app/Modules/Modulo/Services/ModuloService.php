<?php

declare(strict_types=1);

namespace App\Modules\Modulo\Services;

use App\Modules\Auth\Services\SidebarCacheService;
use App\Shared\Models\Formulario;
use App\Shared\Models\Modulo;
use App\Shared\Services\AppCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuloService
{
    public function __construct(
        private readonly SidebarCacheService $sidebarCache,
        private readonly AppCacheService $cache
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listar
    |--------------------------------------------------------------------------
    */

    public function listar(): Collection
    {
        /** @var Collection<int, Modulo> $modulos */
        $modulos =
            $this->cache
                ->remember(
                    AppCacheService::MODULOS,

                    static fn (): Collection =>
                        Modulo::query()
                            ->with('formularios')
                            ->orderBy('orden')
                            ->orderBy('modulo')
                            ->get()
                );

        return $modulos;
    }

    /*
    |--------------------------------------------------------------------------
    | Obtener módulo
    |--------------------------------------------------------------------------
    */

    public function obtenerConFormularios(
        Modulo $modulo
    ): Modulo {
        return $modulo->load(
            'formularios:id,formulario,ruta,descripcion,estado'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $data
    ): Modulo {
        $resultado =
            DB::transaction(
                function () use (
                    $data
                ): Modulo {
                    /*
                    |--------------------------------------------------------------------------
                    | Nuevo módulo siempre al final
                    |--------------------------------------------------------------------------
                    */

                    $ultimoOrden =
                        (int) (
                            Modulo::query()
                                ->max('orden')
                            ?? 0
                        );

                    $modulo =
                        Modulo::query()
                            ->create([
                                'modulo' =>
                                    $data['modulo'],

                                'descripcion' =>
                                    $data['descripcion']
                                    ?? null,

                                'icono' =>
                                    $data['icono']
                                    ?? null,

                                'orden' =>
                                    $ultimoOrden + 1,

                                'estado' =>
                                    $data['estado']
                                    ?? 'Activo',
                            ]);

                    if (
                        array_key_exists(
                            'formularios',
                            $data
                        )
                    ) {
                        $modulo
                            ->formularios()
                            ->sync(
                                $this->normalizarIds(
                                    $data['formularios']
                                    ?? []
                                )
                            );
                    }

                    return $modulo
                        ->fresh()
                        ->load(
                            'formularios'
                        );
                }
            );

        $this->invalidarCacheCatalogos();

        return $resultado;
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function actualizar(
        Modulo $modulo,
        array $data
    ): Modulo {
        $rolesAntes =
            $this->rolesDeModulo(
                (int) $modulo->id
            );

        $resultado =
            DB::transaction(
                function () use (
                    $modulo,
                    $data
                ): Modulo {
                    $campos =
                        [];

                    /*
                     * orden NO se modifica desde el modal normal.
                     *
                     * Solamente mediante /modulos/orden.
                     */

                    foreach (
                        [
                            'modulo',
                            'descripcion',
                            'icono',
                            'estado',
                        ] as $campo
                    ) {
                        if (
                            array_key_exists(
                                $campo,
                                $data
                            )
                        ) {
                            $campos[$campo] =
                                $data[$campo];
                        }
                    }

                    if (
                        $campos !== []
                    ) {
                        $modulo->fill(
                            $campos
                        );

                        $modulo->save();
                    }

                    if (
                        array_key_exists(
                            'formularios',
                            $data
                        )
                    ) {
                        $this
                            ->sincronizarFormulariosDentroTransaccion(
                                $modulo,

                                $this->normalizarIds(
                                    $data['formularios']
                                    ?? []
                                )
                            );
                    }

                    return $modulo
                        ->fresh()
                        ->load(
                            'formularios'
                        );
                }
            );

        $rolesDespues =
            $this->rolesDeModulo(
                (int) $modulo->id
            );

        $this->invalidarRoles(
            array_values(
                array_unique(
                    array_merge(
                        $rolesAntes,
                        $rolesDespues
                    )
                )
            )
        );

        $this->invalidarCacheCatalogos();

        return $resultado;
    }

    /*
    |--------------------------------------------------------------------------
    | Reordenar módulos
    |--------------------------------------------------------------------------
    |
    | Este orden es GLOBAL.
    |
    | Se utiliza en:
    |
    | - listado de módulos;
    | - Sidebar;
    | - todos los roles.
    |
    */

    public function reordenar(
        array $ids
    ): Collection {
        $ids =
            $this->normalizarIds(
                $ids
            );

        /*
        |--------------------------------------------------------------------------
        | Validar listado completo
        |--------------------------------------------------------------------------
        |
        | No permitimos que un cliente antiguo elimine accidentalmente
        | módulos del orden porque no tenía el listado actualizado.
        |
        */

        $actuales =
            Modulo::query()
                ->pluck('id')
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->all();

        $idsComparacion =
            $ids;

        $actualesComparacion =
            $actuales;

        sort(
            $idsComparacion
        );

        sort(
            $actualesComparacion
        );

        if (
            $idsComparacion !==
            $actualesComparacion
        ) {
            throw ValidationException::withMessages([
                'modulo_ids' => [
                    'El listado de módulos cambió. Actualiza la vista antes de guardar el orden.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Guardar orden
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $ids
            ): void {
                $now =
                    now();

                foreach (
                    $ids as
                    $index =>
                    $idModulo
                ) {
                    DB::table(
                        'modulo'
                    )
                        ->where(
                            'id',
                            $idModulo
                        )
                        ->update([
                            'orden' =>
                                $index + 1,

                            'updated_at' =>
                                $now,
                        ]);
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | El orden afecta a todos los Sidebar
        |--------------------------------------------------------------------------
        */

        $this->invalidarSidebarGlobal();

        /*
        |--------------------------------------------------------------------------
        | Catálogos
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheCatalogos();

        /*
        |--------------------------------------------------------------------------
        | Respuesta ordenada
        |--------------------------------------------------------------------------
        */

        return $this->listar();
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar formularios
    |--------------------------------------------------------------------------
    */

    public function sincronizarFormularios(
        Modulo $modulo,
        array $ids
    ): Modulo {
        $rolesAntes =
            $this->rolesDeModulo(
                (int) $modulo->id
            );

        $resultado =
            DB::transaction(
                function () use (
                    $modulo,
                    $ids
                ): Modulo {
                    $this
                        ->sincronizarFormulariosDentroTransaccion(
                            $modulo,

                            $this->normalizarIds(
                                $ids
                            )
                        );

                    return $modulo
                        ->fresh()
                        ->load(
                            'formularios:id,formulario,ruta,descripcion,estado'
                        );
                }
            );

        $rolesDespues =
            $this->rolesDeModulo(
                (int) $modulo->id
            );

        $this->invalidarRoles(
            array_values(
                array_unique(
                    array_merge(
                        $rolesAntes,
                        $rolesDespues
                    )
                )
            )
        );

        $this->invalidarCacheCatalogos();

        return $resultado;
    }

    /*
    |--------------------------------------------------------------------------
    | Desasignar formulario
    |--------------------------------------------------------------------------
    */

    public function desasignarFormulario(
        Modulo $modulo,
        Formulario $formulario
    ): void {
        $asignado =
            $modulo
                ->formularios()
                ->where(
                    'formulario.id',
                    $formulario->id
                )
                ->exists();

        if (
            !$asignado
        ) {
            throw ValidationException::withMessages([
                'formulario' => [
                    'El formulario no está asignado a este módulo.',
                ],
            ]);
        }

        $rolesAntes =
            $this->rolesDeModulo(
                (int) $modulo->id
            );

        DB::transaction(
            function () use (
                $modulo,
                $formulario
            ): void {
                $this
                    ->revocarPermisosDeFormularios(
                        $modulo,

                        [
                            (int) $formulario->id,
                        ]
                    );

                $modulo
                    ->formularios()
                    ->detach(
                        $formulario->id
                    );
            }
        );

        $rolesDespues =
            $this->rolesDeModulo(
                (int) $modulo->id
            );

        $this->invalidarRoles(
            array_values(
                array_unique(
                    array_merge(
                        $rolesAntes,
                        $rolesDespues
                    )
                )
            )
        );

        $this->invalidarCacheCatalogos();
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar módulo
    |--------------------------------------------------------------------------
    */

    public function eliminar(
        Modulo $modulo
    ): void {
        $rolesAfectados =
            $this->rolesDeModulo(
                (int) $modulo->id
            );

        $paresAfectados =
            DB::table(
                'formulario_permiso'
            )
                ->where(
                    'id_modulo',
                    $modulo->id
                )
                ->select(
                    'id_rol',
                    'id_formulario'
                )
                ->distinct()
                ->get()
                ->map(
                    static fn (
                        $row
                    ) => [
                        'id_rol' =>
                            (int) $row->id_rol,

                        'id_formulario' =>
                            (int) $row->id_formulario,
                    ]
                )
                ->all();

        DB::transaction(
            function () use (
                $modulo,
                $paresAfectados
            ): void {
                DB::table(
                    'formulario_permiso'
                )
                    ->where(
                        'id_modulo',
                        $modulo->id
                    )
                    ->delete();

                /*
                 * Solo relaciones.
                 * Los formularios NO se eliminan.
                 */

                $modulo
                    ->formularios()
                    ->detach();

                $modulo
                    ->roles()
                    ->detach();

                $modulo
                    ->delete();

                $this
                    ->limpiarVisibilityHuerfana(
                        $paresAfectados
                    );
            }
        );

        $this->invalidarRoles(
            $rolesAfectados
        );

        $this->invalidarCacheCatalogos();
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronización interna
    |--------------------------------------------------------------------------
    */

    private function sincronizarFormulariosDentroTransaccion(
        Modulo $modulo,
        array $formularios
    ): void {
        $actuales =
            $modulo
                ->formularios()
                ->pluck(
                    'formulario.id'
                )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->all();

        $eliminados =
            array_values(
                array_diff(
                    $actuales,
                    $formularios
                )
            );

        if (
            $eliminados !== []
        ) {
            $this
                ->revocarPermisosDeFormularios(
                    $modulo,
                    $eliminados
                );
        }

        $modulo
            ->formularios()
            ->sync(
                $formularios
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Revocar permisos de formularios
    |--------------------------------------------------------------------------
    */

    private function revocarPermisosDeFormularios(
        Modulo $modulo,
        array $formularios
    ): void {
        if (
            $formularios === []
        ) {
            return;
        }

        $pares =
            DB::table(
                'formulario_permiso'
            )
                ->where(
                    'id_modulo',
                    $modulo->id
                )
                ->whereIn(
                    'id_formulario',
                    $formularios
                )
                ->select(
                    'id_rol',
                    'id_formulario'
                )
                ->distinct()
                ->get()
                ->map(
                    static fn (
                        $row
                    ) => [
                        'id_rol' =>
                            (int) $row->id_rol,

                        'id_formulario' =>
                            (int) $row->id_formulario,
                    ]
                )
                ->all();

        DB::table(
            'formulario_permiso'
        )
            ->where(
                'id_modulo',
                $modulo->id
            )
            ->whereIn(
                'id_formulario',
                $formularios
            )
            ->delete();

        $this->limpiarVisibilityHuerfana(
            $pares
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Limpiar Visibility huérfana
    |--------------------------------------------------------------------------
    */

    private function limpiarVisibilityHuerfana(
        array $pares
    ): void {
        foreach (
            $pares as
            $par
        ) {
            $conservaPermiso =
                DB::table(
                    'formulario_permiso'
                )
                    ->where(
                        'id_rol',
                        $par['id_rol']
                    )
                    ->where(
                        'id_formulario',
                        $par[
                            'id_formulario'
                        ]
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
                    'id_rol',
                    $par['id_rol']
                )
                ->where(
                    'id_formulario',
                    $par[
                        'id_formulario'
                    ]
                )
                ->delete();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Roles de módulo
    |--------------------------------------------------------------------------
    */

    private function rolesDeModulo(
        int $idModulo
    ): array {
        $rolesModulo =
            DB::table(
                'modulo_rol'
            )
                ->where(
                    'id_modulo',
                    $idModulo
                )
                ->pluck(
                    'id_rol'
                );

        $rolesPermisos =
            DB::table(
                'formulario_permiso'
            )
                ->where(
                    'id_modulo',
                    $idModulo
                )
                ->pluck(
                    'id_rol'
                );

        return $rolesModulo
            ->merge(
                $rolesPermisos
            )
            ->map(
                fn ($id) =>
                    (int) $id
            )
            ->unique()
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar roles afectados
    |--------------------------------------------------------------------------
    */

    private function invalidarRoles(
        array $roles
    ): void {
        if (
            $roles === []
        ) {
            return;
        }

        $this->cache
            ->forgetRolesPermisos();

        $this->cache
            ->forgetFormularioAcciones();

        foreach (
            $roles as
            $idRol
        ) {
            $idRol =
                (int) $idRol;

            $this
                ->sidebarCache
                ->forgetRol(
                    $idRol
                );

            $this
                ->cache
                ->forgetRolePermissions(
                    $idRol
                );

            $this
                ->cache
                ->forgetRoleVisibility(
                    $idRol
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar TODOS los sidebar
    |--------------------------------------------------------------------------
    |
    | El orden es global, por eso no basta con limpiar
    | únicamente un rol.
    |
    */

    private function invalidarSidebarGlobal(): void
    {
        $rolesModulo =
            DB::table(
                'modulo_rol'
            )
                ->pluck(
                    'id_rol'
                );

        $rolesPermisos =
            DB::table(
                'formulario_permiso'
            )
                ->pluck(
                    'id_rol'
                );

        $roles =
            $rolesModulo
                ->merge(
                    $rolesPermisos
                )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->unique()
                ->values();

        foreach (
            $roles as
            $idRol
        ) {
            $this
                ->sidebarCache
                ->forgetRol(
                    (int) $idRol
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
        $this->cache
            ->forgetModulos();

        $this->cache
            ->forgetFormularios();

        $this->cache
            ->forgetRoles();
    }

    /*
    |--------------------------------------------------------------------------
    | Normalizar IDs
    |--------------------------------------------------------------------------
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