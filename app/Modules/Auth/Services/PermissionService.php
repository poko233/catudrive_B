<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Shared\Models\User;
use App\Shared\Services\AppCacheService;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function __construct(
        private readonly UIVisibilityService $uiVisibility,
        private readonly AppCacheService $cache
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Permisos del usuario
    |--------------------------------------------------------------------------
    |
    | Los permisos se cachean por usuario porque un usuario
    | puede tener uno o varios roles.
    |
    | Ejemplo:
    |
    | metasoft:usuario:15:permisos
    |
    */

    public function getPermisos(
        User $user
    ): array {
        /** @var array $permisos */
        $permisos =
            $this->cache
                ->rememberForUser(
                    (int) $user->id,
                    'permisos',
                    fn (): array =>
                        $this->buildMapa(
                            (int) $user->id
                        )
                );

        return $permisos;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar permiso
    |--------------------------------------------------------------------------
    */

    public function userHasPermission(
        User $user,
        string $modulo,
        string $formulario,
        string $accion
    ): bool {
        $mapa =
            $this->getPermisos(
                $user
            );

        return
            isset(
                $mapa[
                    $modulo
                ][
                    $formulario
                ]
            )
            &&
            in_array(
                $accion,
                $mapa[
                    $modulo
                ][
                    $formulario
                ],
                true
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Construcción del mapa de permisos
    |--------------------------------------------------------------------------
    */

    private function buildMapa(
        int $idUser
    ): array {
        $rows =
            DB::table(
                'user_rol'
            )
                ->join(
                    'rol',
                    'rol.id',
                    '=',
                    'user_rol.id_rol'
                )
                ->join(
                    'formulario_permiso',
                    'formulario_permiso.id_rol',
                    '=',
                    'rol.id'
                )
                ->join(
                    'modulo',
                    'modulo.id',
                    '=',
                    'formulario_permiso.id_modulo'
                )
                ->join(
                    'formulario',
                    'formulario.id',
                    '=',
                    'formulario_permiso.id_formulario'
                )
                ->join(
                    'accion',
                    'accion.id',
                    '=',
                    'formulario_permiso.id_accion'
                )
                ->where(
                    'user_rol.id_user',
                    $idUser
                )
                ->where(
                    'rol.estado',
                    'Activo'
                )
                ->where(
                    'modulo.estado',
                    'Activo'
                )
                ->where(
                    'formulario.estado',
                    'Activo'
                )
                ->select(
                    'modulo.modulo',
                    'formulario.formulario',
                    'accion.accion'
                )
                ->distinct()
                ->get();

        $mapa =
            [];

        foreach (
            $rows
            as $row
        ) {
            $modulo =
                (string) $row->modulo;

            $formulario =
                (string) $row->formulario;

            $accion =
                (string) $row->accion;

            $mapa[
                $modulo
            ][
                $formulario
            ] ??=
                [];

            if (
                !in_array(
                    $accion,
                    $mapa[
                        $modulo
                    ][
                        $formulario
                    ],
                    true
                )
            ) {
                $mapa[
                    $modulo
                ][
                    $formulario
                ][] =
                    $accion;
            }
        }

        return $mapa;
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar permisos de usuario
    |--------------------------------------------------------------------------
    */

    public function forgetPermisos(
        int $idUser
    ): void {
        $this->cache
            ->forgetForUser(
                $idUser,
                'permisos'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar información derivada de un rol
    |--------------------------------------------------------------------------
    |
    | Cuando cambia un rol debemos invalidar:
    |
    | - permisos de todos sus usuarios;
    | - Sidebar del rol;
    | - Visibility del rol.
    |
    */

    public function forgetPermisosDeRol(
        int $idRol
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Usuarios del rol
        |--------------------------------------------------------------------------
        */

        $usuarios =
            DB::table(
                'user_rol'
            )
                ->where(
                    'id_rol',
                    $idRol
                )
                ->pluck(
                    'id_user'
                );

        /*
        |--------------------------------------------------------------------------
        | Permisos cacheados de usuarios
        |--------------------------------------------------------------------------
        */

        foreach (
            $usuarios
            as $idUser
        ) {
            $this->forgetPermisos(
                (int) $idUser
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sidebar cacheado por rol
        |--------------------------------------------------------------------------
        */

        $this->cache
            ->forgetForRole(
                $idRol,
                'sidebar'
            );

        /*
        |--------------------------------------------------------------------------
        | Visibility
        |--------------------------------------------------------------------------
        */

        $this
            ->uiVisibility
            ->forgetForRole(
                $idRol
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Sidebar del usuario
    |--------------------------------------------------------------------------
    |
    | Un usuario puede tener varios roles.
    |
    | Por eso:
    |
    | 1. obtenemos todos sus roles activos;
    | 2. recuperamos/cacheamos Sidebar por rol;
    | 3. fusionamos módulos;
    | 4. fusionamos formularios;
    | 5. fusionamos acciones;
    | 6. calculamos Visibility;
    | 7. ordenamos módulos por modulo.orden.
    |
    */

    public function getSidebar(
        User $user
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Roles activos
        |--------------------------------------------------------------------------
        */

        $roles =
            DB::table(
                'user_rol'
            )
                ->join(
                    'rol',
                    'rol.id',
                    '=',
                    'user_rol.id_rol'
                )
                ->where(
                    'user_rol.id_user',
                    $user->id
                )
                ->where(
                    'rol.estado',
                    'Activo'
                )
                ->pluck(
                    'rol.id'
                )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->unique()
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Resultado combinado
        |--------------------------------------------------------------------------
        */

        $merged =
            [];

        /*
        |--------------------------------------------------------------------------
        | Selectores Visibility por formulario
        |--------------------------------------------------------------------------
        */

        $selectorsByForm =
            [];

        /*
        |--------------------------------------------------------------------------
        | Procesar cada rol
        |--------------------------------------------------------------------------
        */

        foreach (
            $roles
            as $idRol
        ) {
            /*
             * Utilizamos AppCacheService.
             *
             * Ya NO utilizamos:
             *
             * Cache::remember()
             * self::TTL
             * sidebarKey()
             */

            /** @var array $rolSidebar */
            $rolSidebar =
                $this->cache
                    ->rememberForRole(
                        $idRol,
                        'sidebar',
                        fn (): array =>
                            $this->buildSidebarParaRol(
                                $idRol
                            )
                    );

            /*
            |--------------------------------------------------------------------------
            | Fusionar módulos
            |--------------------------------------------------------------------------
            */

            foreach (
                $rolSidebar
                as $idModulo =>
                $modulo
            ) {
                if (
                    !isset(
                        $merged[
                            $idModulo
                        ]
                    )
                ) {
                    $merged[
                        $idModulo
                    ] =
                        $modulo;
                }

                /*
                |--------------------------------------------------------------------------
                | Fusionar formularios
                |--------------------------------------------------------------------------
                */

                foreach (
                    $modulo[
                        'formularios'
                    ]
                    as $idFormulario =>
                    $formulario
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Visibility
                    |--------------------------------------------------------------------------
                    */

                    $selectorsByForm[
                        $idFormulario
                    ][] =
                        $formulario[
                            'selectores_ocultos'
                        ]
                        ?? [];

                    /*
                    |--------------------------------------------------------------------------
                    | Formulario todavía no agregado
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset(
                            $merged[
                                $idModulo
                            ][
                                'formularios'
                            ][
                                $idFormulario
                            ]
                        )
                    ) {
                        $merged[
                            $idModulo
                        ][
                            'formularios'
                        ][
                            $idFormulario
                        ] =
                            $formulario;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Fusionar acciones
                    |--------------------------------------------------------------------------
                    */

                    $merged[
                        $idModulo
                    ][
                        'formularios'
                    ][
                        $idFormulario
                    ][
                        'acciones'
                    ] =
                        array_values(
                            array_unique(
                                array_merge(
                                    $merged[
                                        $idModulo
                                    ][
                                        'formularios'
                                    ][
                                        $idFormulario
                                    ][
                                        'acciones'
                                    ],

                                    $formulario[
                                        'acciones'
                                    ]
                                )
                            )
                        );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Resolver Visibility entre múltiples roles
        |--------------------------------------------------------------------------
        |
        | Si el usuario posee múltiples roles, un selector solamente
        | debe permanecer oculto cuando todos los roles coinciden
        | en ocultarlo.
        |
        */

        foreach (
            $merged
            as &$modulo
        ) {
            foreach (
                $modulo[
                    'formularios'
                ]
                as $idFormulario =>
                &$formulario
            ) {
                $sets =
                    $selectorsByForm[
                        $idFormulario
                    ]
                    ?? [];

                if (
                    $sets === []
                ) {
                    $formulario[
                        'selectores_ocultos'
                    ] =
                        [];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Primera colección
                |--------------------------------------------------------------------------
                */

                $intersection =
                    array_values(
                        $sets[
                            0
                        ]
                    );

                /*
                |--------------------------------------------------------------------------
                | Intersección
                |--------------------------------------------------------------------------
                */

                for (
                    $i = 1;
                    $i < count(
                        $sets
                    );
                    $i++
                ) {
                    $intersection =
                        array_values(
                            array_intersect(
                                $intersection,
                                $sets[
                                    $i
                                ]
                            )
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Resultado
                |--------------------------------------------------------------------------
                */

                $formulario[
                    'selectores_ocultos'
                ] =
                    array_values(
                        array_unique(
                            $intersection
                        )
                    );
            }

            unset(
                $formulario
            );
        }

        unset(
            $modulo
        );

        /*
        |--------------------------------------------------------------------------
        | Orden global de módulos
        |--------------------------------------------------------------------------
        |
        | El campo:
        |
        | modulo.orden
        |
        | controla exactamente cómo se muestran los módulos
        | en el Sidebar.
        |
        */

        uasort(
            $merged,

            static function (
                array $a,
                array $b
            ): int {
                /*
                |--------------------------------------------------------------------------
                | Orden numérico
                |--------------------------------------------------------------------------
                */

                $ordenA =
                    (int) (
                        $a[
                            'orden'
                        ]
                        ?? 0
                    );

                $ordenB =
                    (int) (
                        $b[
                            'orden'
                        ]
                        ?? 0
                    );

                $comparacion =
                    $ordenA
                    <=>
                    $ordenB;

                if (
                    $comparacion !==
                    0
                ) {
                    return $comparacion;
                }

                /*
                |--------------------------------------------------------------------------
                | Respaldo alfabético
                |--------------------------------------------------------------------------
                */

                return strnatcasecmp(
                    (string) (
                        $a[
                            'nombre'
                        ]
                        ?? ''
                    ),
                    (string) (
                        $b[
                            'nombre'
                        ]
                        ?? ''
                    )
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Convertir colecciones asociativas en arrays JSON
        |--------------------------------------------------------------------------
        */

        return array_values(
            array_map(
                static function (
                    array $modulo
                ): array {
                    $modulo[
                        'formularios'
                    ] =
                        array_values(
                            $modulo[
                                'formularios'
                            ]
                        );

                    return $modulo;
                },

                $merged
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Construcción del Sidebar por rol
    |--------------------------------------------------------------------------
    */

    private function buildSidebarParaRol(
        int $idRol
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Permisos
        |--------------------------------------------------------------------------
        */

        $rows =
            DB::table(
                'formulario_permiso'
            )
                ->join(
                    'modulo',
                    'modulo.id',
                    '=',
                    'formulario_permiso.id_modulo'
                )
                ->join(
                    'formulario',
                    'formulario.id',
                    '=',
                    'formulario_permiso.id_formulario'
                )
                ->join(
                    'accion',
                    'accion.id',
                    '=',
                    'formulario_permiso.id_accion'
                )
                ->where(
                    'formulario_permiso.id_rol',
                    $idRol
                )
                ->where(
                    'modulo.estado',
                    'Activo'
                )
                ->where(
                    'formulario.estado',
                    'Activo'
                )
                ->select(
                    /*
                    |--------------------------------------------------------------------------
                    | Módulo
                    |--------------------------------------------------------------------------
                    */

                    'modulo.id as id_modulo',
                    'modulo.modulo',
                    'modulo.icono',
                    'modulo.orden',

                    /*
                    |--------------------------------------------------------------------------
                    | Formulario
                    |--------------------------------------------------------------------------
                    */

                    'formulario.id as id_formulario',
                    'formulario.formulario',
                    'formulario.ruta',

                    /*
                    |--------------------------------------------------------------------------
                    | Acción
                    |--------------------------------------------------------------------------
                    */

                    'accion.accion'
                )
                ->distinct()

                /*
                |--------------------------------------------------------------------------
                | Orden del Sidebar
                |--------------------------------------------------------------------------
                */

                ->orderBy(
                    'modulo.orden'
                )
                ->orderBy(
                    'modulo.modulo'
                )
                ->orderBy(
                    'formulario.formulario'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Visibility del rol
        |--------------------------------------------------------------------------
        */

        $visibility =
            $this
                ->uiVisibility
                ->getVisibilityForRole(
                    $idRol
                );

        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        $sidebar =
            [];

        /*
        |--------------------------------------------------------------------------
        | Construir estructura
        |--------------------------------------------------------------------------
        */

        foreach (
            $rows
            as $row
        ) {
            $idModulo =
                (int)
                $row->id_modulo;

            $idFormulario =
                (int)
                $row->id_formulario;

            /*
            |--------------------------------------------------------------------------
            | Módulo
            |--------------------------------------------------------------------------
            */

            if (
                !isset(
                    $sidebar[
                        $idModulo
                    ]
                )
            ) {
                $sidebar[
                    $idModulo
                ] = [
                    'id' =>
                        $idModulo,

                    'nombre' =>
                        (string)
                        $row->modulo,

                    'icono' =>
                        $row->icono,

                    /*
                     * Campo que determina la posición
                     * global dentro del Sidebar.
                     */

                    'orden' =>
                        (int)
                        $row->orden,

                    'formularios' =>
                        [],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Formulario
            |--------------------------------------------------------------------------
            */

            if (
                !isset(
                    $sidebar[
                        $idModulo
                    ][
                        'formularios'
                    ][
                        $idFormulario
                    ]
                )
            ) {
                $sidebar[
                    $idModulo
                ][
                    'formularios'
                ][
                    $idFormulario
                ] = [
                    'id' =>
                        $idFormulario,

                    'nombre' =>
                        (string)
                        $row->formulario,

                    'ruta' =>
                        $row->ruta,

                    'acciones' =>
                        [],

                    'selectores_ocultos' =>
                        $visibility[
                            $idFormulario
                        ]
                        ?? [],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Acción
            |--------------------------------------------------------------------------
            */

            if (
                !in_array(
                    $row->accion,

                    $sidebar[
                        $idModulo
                    ][
                        'formularios'
                    ][
                        $idFormulario
                    ][
                        'acciones'
                    ],

                    true
                )
            ) {
                $sidebar[
                    $idModulo
                ][
                    'formularios'
                ][
                    $idFormulario
                ][
                    'acciones'
                ][] =
                    (string)
                    $row->accion;
            }
        }

        return $sidebar;
    }
}