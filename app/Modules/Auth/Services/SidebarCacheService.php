<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\DB;

class SidebarCacheService
{
    public function __construct(
        private readonly PermissionService $permissionService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Rol
    |--------------------------------------------------------------------------
    */

    public function forgetRol(
        int $idRol
    ): void {
        $this
            ->permissionService
            ->forgetPermisosDeRol(
                $idRol
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Módulo
    |--------------------------------------------------------------------------
    */

    public function forgetByModulo(
        int $idModulo
    ): void {
        /*
         * Roles que tienen el módulo asignado.
         */

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

        /*
         * Roles que poseen permisos dentro del módulo.
         */

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
            $roles
            as $idRol
        ) {
            $this->forgetRol(
                $idRol
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Formulario
    |--------------------------------------------------------------------------
    */

    public function forgetByFormulario(
        int $idFormulario
    ): void {
        $roles =
            DB::table(
                'formulario_permiso'
            )
                ->where(
                    'id_formulario',
                    $idFormulario
                )
                ->pluck(
                    'id_rol'
                )
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->unique()
                ->values();

        foreach (
            $roles
            as $idRol
        ) {
            $this->forgetRol(
                $idRol
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Usuario
    |--------------------------------------------------------------------------
    */

    public function forgetUser(
        int $idUser
    ): void {
        $this
            ->permissionService
            ->forgetPermisos(
                $idUser
            );
    }
}