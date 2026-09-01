<?php

declare(strict_types=1);

namespace App\Modules\Rol\Services;

use App\Modules\Auth\Services\SidebarCacheService;
use App\Modules\Rol\Repositories\RolRepository;
use App\Shared\Models\FormularioPermiso;
use App\Shared\Models\Modulo;
use App\Shared\Models\Rol;
use App\Shared\Services\AppCacheService;
use App\Shared\Services\AuditService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RolService
{
    public function __construct(
        private readonly RolRepository $repo,
        private readonly SidebarCacheService $sidebarCache,
        private readonly AuditService $audit,
        private readonly AppCacheService $cache
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listar roles
    |--------------------------------------------------------------------------
    */

    public function listar(
        array $filtros
    ): LengthAwarePaginator {
        return $this->repo
            ->paginar(
                $filtros
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Detalle del rol
    |--------------------------------------------------------------------------
    */

    public function detalle(
        Rol $rol
    ): Rol {
        /** @var Rol $resultado */
        $resultado =
            $this->cache
                ->rememberForRole(
                    (int) $rol->id,
                    'detalle',
                    fn (): Rol =>
                        $this->repo
                            ->conPermisos(
                                $rol
                            )
                );

        return $resultado;
    }

    /*
    |--------------------------------------------------------------------------
    | Crear rol
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $datos
    ): Rol {
        $rol =
            $this->repo
                ->crear([
                    'rol' =>
                        $datos['rol'],

                    'descripcion' =>
                        $datos['descripcion']
                        ?? null,

                    'estado' =>
                        $datos['estado']
                        ?? 'Activo',
                ]);

        $this->invalidarCacheGlobalRoles();

        return $rol;
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar rol
    |--------------------------------------------------------------------------
    */

    public function actualizar(
        Rol $rol,
        array $datos
    ): Rol {
        /*
         * El Superadmin continúa protegido:
         *
         * - no puede renombrarse;
         * - no puede desactivarse.
         *
         * Sí puede modificar:
         *
         * - módulos;
         * - permisos;
         * - Visibility.
         */

        $this->protegerSuperRolEnActualizacion(
            $rol,
            $datos
        );

        $actualizado =
            $this->repo
                ->actualizar(
                    $rol,
                    $datos
                );

        $this->invalidarCacheRol(
            (int) $rol->id
        );

        return $actualizado;
    }

    /*
    |--------------------------------------------------------------------------
    | Obtener rol con módulos
    |--------------------------------------------------------------------------
    */

    public function obtenerConModulos(
        Rol $rol
    ): Rol {
        /** @var Rol $resultado */
        $resultado =
            $this->cache
                ->rememberForRole(
                    (int) $rol->id,
                    'modulos',
                    fn (): Rol =>
                        $rol->load(
                            'modulos:id,modulo,icono,descripcion,estado'
                        )
                );

        return $resultado;
    }

    /*
    |--------------------------------------------------------------------------
    | Listar roles con módulos
    |--------------------------------------------------------------------------
    |
    | Endpoint masivo para evitar N+1 HTTP.
    |
    */

    public function listarConModulos(): Collection
    {
        return Rol::query()
            ->select([
                'id',
                'rol',
                'descripcion',
                'estado',
            ])
            ->with([
                'modulos' =>
                    static function (
                        $query
                    ): void {
                        $query
                            ->select([
                                'modulo.id',
                                'modulo.modulo',
                                'modulo.icono',
                                'modulo.descripcion',
                                'modulo.estado',
                            ])
                            ->orderBy(
                                'modulo.modulo'
                            );
                    },
            ])
            ->orderBy(
                'rol'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar módulos
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    |
    | El Superadmin SÍ puede modificar los módulos asignados.
    |
    | Esto permite administrar desde la vista Módulo/Rol:
    |
    | - asignar módulos;
    | - quitar módulos;
    | - sincronizar el conjunto completo.
    |
    | Al quitar un módulo también se revocan:
    |
    | - permisos asociados a ese módulo;
    | - Visibility huérfana cuando corresponda.
    |
    */

    public function sincronizarModulos(
        Rol $rol,
        array $moduloIds
    ): Rol {
        /*
         * NO bloquear al Superadmin aquí.
         *
         * Antes existía:
         *
         * $this->assertSuperRoleStructuralChangeAllowed(...)
         *
         * pero los módulos del Superadmin deben poder
         * configurarse desde la administración.
         */

        $ids =
            $this->normalizarIds(
                $moduloIds
            );

        /*
        |--------------------------------------------------------------------------
        | Módulos actuales
        |--------------------------------------------------------------------------
        */

        $actuales =
            $rol
                ->modulos()
                ->pluck(
                    'modulo.id'
                )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Detectar eliminados
        |--------------------------------------------------------------------------
        */

        $eliminados =
            array_values(
                array_diff(
                    $actuales,
                    $ids
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Auditoría - estado anterior
        |--------------------------------------------------------------------------
        */

        $before = [
            'modulo_ids' =>
                $actuales,
        ];

        /*
        |--------------------------------------------------------------------------
        | Sincronizar
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $rol,
                $ids,
                $eliminados
            ): void {
                /*
                 * Si se quitaron módulos,
                 * primero eliminamos permisos dependientes.
                 */

                if (
                    $eliminados !== []
                ) {
                    $this->revocarPermisosDeModulos(
                        $rol,
                        $eliminados
                    );
                }

                /*
                 * Sync actualiza solamente modulo_rol.
                 *
                 * NO elimina registros de modulo.
                 */

                $rol
                    ->modulos()
                    ->sync(
                        $ids
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheRol(
            (int) $rol->id,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        $this->audit->log(
            action:
                'Editar',

            resource:
                'ModulosRol',

            resourceId:
                $rol->id,

            before:
                $before,

            after: [
                'modulo_ids' =>
                    $ids,
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Respuesta actualizada
        |--------------------------------------------------------------------------
        */

        return $this->obtenerConModulos(
            $rol
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Desasignar módulo
    |--------------------------------------------------------------------------
    |
    | El Superadmin también puede quitar módulos individualmente.
    |
    | Se elimina únicamente:
    |
    | - relación modulo_rol;
    | - permisos dependientes;
    | - Visibility huérfana.
    |
    | NO se elimina el módulo.
    |
    */

    public function desasignarModulo(
        Rol $rol,
        Modulo $modulo
    ): void {
        /*
         * NO bloquear al Superadmin aquí.
         */

        /*
        |--------------------------------------------------------------------------
        | Verificar asignación
        |--------------------------------------------------------------------------
        */

        $asignado =
            $rol
                ->modulos()
                ->where(
                    'modulo.id',
                    $modulo->id
                )
                ->exists();

        if (!$asignado) {
            throw ValidationException::withMessages([
                'modulo' => [
                    'El módulo no está asignado al rol.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Eliminar relación
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $rol,
                $modulo
            ): void {
                /*
                 * Primero eliminamos permisos que dependan
                 * del módulo que estamos retirando.
                 */

                $this->revocarPermisosDeModulos(
                    $rol,
                    [
                        (int) $modulo->id,
                    ]
                );

                /*
                 * Detach elimina únicamente la relación
                 * de modulo_rol.
                 *
                 * NO elimina el módulo.
                 */

                $rol
                    ->modulos()
                    ->detach(
                        $modulo->id
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheRol(
            (int) $rol->id,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        $this->audit->log(
            action:
                'Editar',

            resource:
                'ModulosRol',

            resourceId:
                $rol->id,

            before: [
                'modulo_eliminado' =>
                    (int) $modulo->id,
            ],

            after:
                [],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar permisos
    |--------------------------------------------------------------------------
    |
    | El Superadmin puede modificar sus permisos.
    |
    | Esto permite editar desde la matriz:
    |
    | - Ver;
    | - Crear;
    | - Editar;
    | - Eliminar.
    |
    | El rol principal sigue protegido contra:
    |
    | - renombrado;
    | - desactivación;
    | - eliminación.
    |
    */

    public function sincronizarPermisos(
        Rol $rol,
        array $permisos
    ): Rol {
        /*
         * NO bloquear al Superadmin aquí.
         */

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
        | Validar relaciones
        |--------------------------------------------------------------------------
        */

        $this->validarPermisosDelRol(
            $rol,
            $normalizados
        );

        /*
        |--------------------------------------------------------------------------
        | Estado anterior
        |--------------------------------------------------------------------------
        */

        $before =
            $rol
                ->permisos()
                ->get()
                ->map(
                    static fn (
                        FormularioPermiso $permiso
                    ) => [
                        'id' =>
                            (int) $permiso->id,

                        'id_modulo' =>
                            (int) $permiso->id_modulo,

                        'id_formulario' =>
                            (int) $permiso->id_formulario,

                        'id_accion' =>
                            (int) $permiso->id_accion,
                    ]
                )
                ->values()
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Guardar permisos
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $rol,
                $normalizados
            ): void {
                /*
                |--------------------------------------------------------------------------
                | Eliminar permisos anteriores
                |--------------------------------------------------------------------------
                */

                $rol
                    ->permisos()
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Insertar permisos nuevos
                |--------------------------------------------------------------------------
                */

                if (
                    $normalizados !== []
                ) {
                    $now =
                        now();

                    $filas =
                        [];

                    foreach (
                        $normalizados
                        as $permiso
                    ) {
                        foreach (
                            $permiso[
                                'acciones'
                            ]
                            as $idAccion
                        ) {
                            $filas[] = [
                                'id_rol' =>
                                    (int) $rol->id,

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
                                    $idAccion,

                                'created_at' =>
                                    $now,

                                'updated_at' =>
                                    $now,
                            ];
                        }
                    }

                    if (
                        $filas !== []
                    ) {
                        FormularioPermiso::query()
                            ->insert(
                                $filas
                            );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Limpiar Visibility huérfana
                |--------------------------------------------------------------------------
                */

                $this->limpiarVisibilitySinPermisos(
                    (int) $rol->id
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheRol(
            (int) $rol->id,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Estado nuevo
        |--------------------------------------------------------------------------
        */

        $after =
            $rol
                ->permisos()
                ->get()
                ->map(
                    static fn (
                        FormularioPermiso $permiso
                    ) => [
                        'id' =>
                            (int) $permiso->id,

                        'id_modulo' =>
                            (int) $permiso->id_modulo,

                        'id_formulario' =>
                            (int) $permiso->id_formulario,

                        'id_accion' =>
                            (int) $permiso->id_accion,
                    ]
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
                $rol->id,

            before: [
                'permisos' =>
                    $before,
            ],

            after: [
                'permisos' =>
                    $after,
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Respuesta
        |--------------------------------------------------------------------------
        */

        return $this->repo
            ->conPermisos(
                $rol
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar rol
    |--------------------------------------------------------------------------
    |
    | El Superadmin NO puede eliminarse.
    |
    */

    public function eliminar(
        Rol $rol
    ): void {
        /*
         * Esta protección SÍ debe mantenerse.
         */

        $this->assertSuperRoleStructuralChangeAllowed(
            $rol,
            'No se puede eliminar el rol principal del sistema.'
        );

        /*
        |--------------------------------------------------------------------------
        | No eliminar roles con usuarios
        |--------------------------------------------------------------------------
        */

        if (
            $rol
                ->usuarios()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'rol' => [
                    'No se puede eliminar un rol que tiene usuarios asignados.',
                ],
            ]);
        }

        $idRol =
            (int) $rol->id;

        /*
        |--------------------------------------------------------------------------
        | Eliminar
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $rol
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
                        'id_rol',
                        $rol->id
                    )
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Permisos
                |--------------------------------------------------------------------------
                */

                $rol
                    ->permisos()
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Módulos
                |--------------------------------------------------------------------------
                */

                $rol
                    ->modulos()
                    ->detach();

                /*
                |--------------------------------------------------------------------------
                | Rol
                |--------------------------------------------------------------------------
                */

                $this->repo
                    ->eliminar(
                        $rol
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheRol(
            $idRol,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Listado completo con permisos
    |--------------------------------------------------------------------------
    */

    public function listarConPermisos(): Collection
    {
        /** @var Collection $roles */
        $roles =
            $this->cache
                ->remember(
                    AppCacheService::ROLES_PERMISOS,

                    fn (): Collection =>
                        $this->repo
                            ->todosConPermisos()
                );

        return $roles;
    }

    /*
    |--------------------------------------------------------------------------
    | Revocar permisos de módulos
    |--------------------------------------------------------------------------
    |
    | Al retirar un módulo de un rol:
    |
    | 1. busca formularios afectados;
    | 2. elimina permisos del módulo;
    | 3. limpia Visibility de formularios que ya no
    |    tengan permisos dentro del rol.
    |
    */

    /**
     * @param array<int, int> $moduloIds
     */
    private function revocarPermisosDeModulos(
        Rol $rol,
        array $moduloIds
    ): void {
        if (
            $moduloIds === []
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Formularios afectados
        |--------------------------------------------------------------------------
        */

        $formulariosAfectados =
            $rol
                ->permisos()
                ->whereIn(
                    'id_modulo',
                    $moduloIds
                )
                ->pluck(
                    'id_formulario'
                )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Eliminar permisos
        |--------------------------------------------------------------------------
        */

        $rol
            ->permisos()
            ->whereIn(
                'id_modulo',
                $moduloIds
            )
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Limpiar Visibility
        |--------------------------------------------------------------------------
        */

        foreach (
            $formulariosAfectados
            as $idFormulario
        ) {
            $conservaPermiso =
                $rol
                    ->permisos()
                    ->where(
                        'id_formulario',
                        $idFormulario
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
                    $rol->id
                )
                ->where(
                    'id_formulario',
                    $idFormulario
                )
                ->delete();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validar permisos
    |--------------------------------------------------------------------------
    |
    | Verifica:
    |
    | - módulo asignado al rol;
    | - formulario perteneciente al módulo.
    |
    */

    private function validarPermisosDelRol(
        Rol $rol,
        array $permisos
    ): void {
        if (
            $permisos === []
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Módulos asignados al rol
        |--------------------------------------------------------------------------
        */

        $modulosAsignados =
            $rol
                ->modulos()
                ->pluck(
                    'modulo.id'
                )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->all();

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
            /*
            |--------------------------------------------------------------------------
            | Módulo pertenece al rol
            |--------------------------------------------------------------------------
            */

            if (
                !in_array(
                    $permiso[
                        'id_modulo'
                    ],
                    $modulosAsignados,
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    "permisos.{$indice}.id_modulo" => [
                        'El módulo debe estar asignado al rol antes de otorgar permisos.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Formulario pertenece al módulo
            |--------------------------------------------------------------------------
            */

            $relacionValida =
                DB::table(
                    'formulario_modulo'
                )
                    ->where(
                        'id_modulo',
                        $permiso[
                            'id_modulo'
                        ]
                    )
                    ->where(
                        'id_formulario',
                        $permiso[
                            'id_formulario'
                        ]
                    )
                    ->exists();

            if (
                !$relacionValida
            ) {
                throw ValidationException::withMessages([
                    "permisos.{$indice}.id_formulario" => [
                        'El formulario no pertenece al módulo indicado.',
                    ],
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Limpiar Visibility sin permisos
    |--------------------------------------------------------------------------
    |
    | Elimina configuraciones de Visibility para formularios
    | que ya no poseen ningún permiso dentro del rol.
    |
    */

    private function limpiarVisibilitySinPermisos(
        int $idRol
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Formularios con permisos
        |--------------------------------------------------------------------------
        */

        $formulariosConPermiso =
            DB::table(
                'formulario_permiso'
            )
                ->where(
                    'id_rol',
                    $idRol
                )
                ->pluck(
                    'id_formulario'
                )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Visibility del rol
        |--------------------------------------------------------------------------
        */

        $query =
            DB::table(
                'formulario_accion'
            )
                ->where(
                    'id_rol',
                    $idRol
                );

        /*
        |--------------------------------------------------------------------------
        | Sin formularios con permisos
        |--------------------------------------------------------------------------
        */

        if (
            $formulariosConPermiso === []
        ) {
            $query->delete();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Eliminar únicamente huérfanos
        |--------------------------------------------------------------------------
        */

        $query
            ->whereNotIn(
                'id_formulario',
                $formulariosConPermiso
            )
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar caché global de roles
    |--------------------------------------------------------------------------
    */

    private function invalidarCacheGlobalRoles(): void
    {
        /*
         * Catálogo/listado de roles.
         */

        $this->cache
            ->forgetRoles();

        /*
         * GET /api/roles/permisos
         */

        $this->cache
            ->forgetRolesPermisos();
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar caché completa del rol
    |--------------------------------------------------------------------------
    */

    private function invalidarCacheRol(
        int $idRol,
        bool $visibilityPuedeCambiar = false
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Sidebar / auth
        |--------------------------------------------------------------------------
        */

        $this
            ->sidebarCache
            ->forgetRol(
                $idRol
            );

        /*
        |--------------------------------------------------------------------------
        | Detalle
        |--------------------------------------------------------------------------
        */

        $this->cache
            ->forgetForRole(
                $idRol,
                'detalle'
            );

        /*
        |--------------------------------------------------------------------------
        | Módulos
        |--------------------------------------------------------------------------
        */

        $this->cache
            ->forgetForRole(
                $idRol,
                'modulos'
            );

        /*
        |--------------------------------------------------------------------------
        | Listados globales
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheGlobalRoles();

        /*
        |--------------------------------------------------------------------------
        | Visibility
        |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | Normalizar permisos
    |--------------------------------------------------------------------------
    */

    /**
     * Agrupa permisos iguales y elimina
     * acciones duplicadas.
     *
     * @return array<int, array{
     *     id_modulo:int,
     *     id_formulario:int,
     *     acciones:array<int,int>
     * }>
     */
    private function normalizarPermisos(
        array $permisos
    ): array {
        $resultado =
            [];

        foreach (
            $permisos
            as $permiso
        ) {
            /*
            |--------------------------------------------------------------------------
            | IDs
            |--------------------------------------------------------------------------
            */

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
            | Clave compuesta
            |--------------------------------------------------------------------------
            */

            $key =
                $idModulo
                . ':'
                . $idFormulario;

            /*
            |--------------------------------------------------------------------------
            | Inicializar
            |--------------------------------------------------------------------------
            */

            $resultado[$key] ??= [
                'id_modulo' =>
                    $idModulo,

                'id_formulario' =>
                    $idFormulario,

                'acciones' =>
                    [],
            ];

            /*
            |--------------------------------------------------------------------------
            | Acciones
            |--------------------------------------------------------------------------
            */

            foreach (
                $permiso[
                    'acciones'
                ]
                as $idAccion
            ) {
                $resultado[
                    $key
                ][
                    'acciones'
                ][] =
                    (int)
                    $idAccion;
            }

            /*
            |--------------------------------------------------------------------------
            | Eliminar duplicados
            |--------------------------------------------------------------------------
            */

            $resultado[
                $key
            ][
                'acciones'
            ] =
                array_values(
                    array_unique(
                        $resultado[
                            $key
                        ][
                            'acciones'
                        ]
                    )
                );
        }

        return array_values(
            $resultado
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Proteger Superadmin al actualizar
    |--------------------------------------------------------------------------
    |
    | Esta protección SÍ se mantiene.
    |
    | El Superadmin:
    |
    | - no puede cambiar de nombre;
    | - no puede pasar a Inactivo.
    |
    */

    private function protegerSuperRolEnActualizacion(
        Rol $rol,
        array $datos
    ): void {
        if (
            !$this->esSuperRol(
                $rol
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | No renombrar
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $datos[
                    'rol'
                ]
            )
            &&
            trim(
                (string)
                $datos[
                    'rol'
                ]
            )
            !==
            trim(
                (string)
                $rol->rol
            )
        ) {
            throw ValidationException::withMessages([
                'rol' => [
                    'No se puede renombrar el rol principal del sistema.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | No desactivar
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $datos[
                    'estado'
                ]
            )
            &&
            mb_strtoupper(
                trim(
                    (string)
                    $datos[
                        'estado'
                    ]
                )
            )
            !==
            'ACTIVO'
        ) {
            throw ValidationException::withMessages([
                'estado' => [
                    'No se puede desactivar el rol principal del sistema.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bloquear eliminación estructural del Superadmin
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    |
    | Ya NO se utiliza para:
    |
    | - sincronizar módulos;
    | - desasignar módulos;
    | - sincronizar permisos.
    |
    | Se mantiene para impedir eliminar
    | completamente el rol principal.
    |
    */

    private function assertSuperRoleStructuralChangeAllowed(
        Rol $rol,
        string $message
    ): void {
        if (
            $this->esSuperRol(
                $rol
            )
        ) {
            throw ValidationException::withMessages([
                'rol' => [
                    $message,
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Determinar si es Superadmin
    |--------------------------------------------------------------------------
    */

    private function esSuperRol(
        Rol $rol
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Configuración
        |--------------------------------------------------------------------------
        */

        $superRoles =
            array_values(
                array_filter(
                    config(
                        'rbac.super_roles',
                        []
                    ),

                    static fn (
                        $nombre
                    ) =>
                        is_string(
                            $nombre
                        )
                        &&
                        trim(
                            $nombre
                        ) !== ''
                )
            );

        if (
            $superRoles === []
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Comparar
        |--------------------------------------------------------------------------
        */

        foreach (
            $superRoles
            as $nombre
        ) {
            if (
                mb_strtolower(
                    trim(
                        (string)
                        $nombre
                    )
                )
                ===
                mb_strtolower(
                    trim(
                        (string)
                        $rol->rol
                    )
                )
            ) {
                return true;
            }
        }

        return false;
    }
}