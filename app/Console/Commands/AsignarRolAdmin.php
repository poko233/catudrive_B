<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Auth\Services\PermissionService;
use App\Shared\Models\Rol;
use App\Shared\Models\User;
use Illuminate\Console\Command;

class AsignarRolAdmin extends Command
{
    protected $signature =
        'rbac:admin
        {user_id : ID del usuario al que se asignará el primer super_rol de config/rbac.php}';

    protected $description =
        'Asigna el rol Administrador a un usuario (puerta de rescate para acceso perdido)';

    public function __construct(
        private readonly PermissionService $permissionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        /*
        |--------------------------------------------------------------------------
        | Obtener usuario
        |--------------------------------------------------------------------------
        */

        $userId =
            (int) $this->argument(
                'user_id'
            );

        $user =
            User::query()
                ->find(
                    $userId
                );

        if (!$user) {
            $this->error(
                "No se encontró un usuario con id={$userId}."
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener nombre del super rol
        |--------------------------------------------------------------------------
        */

        $nombreRol =
            config(
                'rbac.super_roles.0',
                'Administrador'
            );

        /*
        |--------------------------------------------------------------------------
        | Obtener o crear super rol
        |--------------------------------------------------------------------------
        |
        | Rol es un catálogo global.
        | No posee id_empresa.
        |
        */

        $rol =
            Rol::query()
                ->firstOrCreate(
                    [
                        'rol' =>
                            $nombreRol,
                    ],
                    [
                        'descripcion' =>
                            'Rol con acceso total al sistema.',

                        'estado' =>
                            'Activo',
                    ]
                );

        /*
        |--------------------------------------------------------------------------
        | Verificar asignación
        |--------------------------------------------------------------------------
        */

        $yaAsignado =
            $user
                ->roles()
                ->where(
                    'rol.id',
                    $rol->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | Asignar rol
        |--------------------------------------------------------------------------
        */

        if ($yaAsignado) {
            $this->info(
                "El usuario '{$user->usuario}' ya tiene el rol '{$rol->rol}'."
            );
        } else {
            $user
                ->roles()
                ->attach(
                    $rol->id
                );

            $this->info(
                "Rol '{$rol->rol}' asignado a '{$user->usuario}' (id={$user->id})."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Invalidar permisos cacheados
        |--------------------------------------------------------------------------
        |
        | Es obligatorio porque user_rol acaba de cambiar.
        |
        | PermissionService se encarga de utilizar la infraestructura
        | centralizada de AppCacheService.
        |
        */

        $this->permissionService
            ->forgetPermisos(
                (int) $user->id
            );

        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        $this->info(
            "Caché de permisos invalidada. "
            . "El usuario '{$user->usuario}' ahora posee el rol '{$rol->rol}'."
        );

        return self::SUCCESS;
    }
}