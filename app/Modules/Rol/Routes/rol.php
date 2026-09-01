<?php

declare(strict_types=1);

use App\Modules\Rol\Controllers\ModuloRolController;
use App\Modules\Rol\Controllers\RolController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])
    ->prefix('roles')
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Listados especiales
        |--------------------------------------------------------------------------
        |
        | Deben declararse ANTES de /{rol}.
        |
        */

        Route::get(
            '/permisos',
            [
                RolController::class,
                'todosConPermisos',
            ]
        )
            ->middleware(
                'permiso:Configuracion,Roles,Ver'
            )
            ->name(
                'roles.all-permissions'
            );

        /*
        |--------------------------------------------------------------------------
        | Listado masivo Módulo ↔ Rol
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/modulos/asignaciones',
            [
                ModuloRolController::class,
                'index',
            ]
        )
            ->middleware(
                'permiso:Configuracion,Roles,Ver'
            )
            ->name(
                'roles.modules.index'
            );

        /*
        |--------------------------------------------------------------------------
        | CRUD Roles
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/',
            [
                RolController::class,
                'index',
            ]
        )
            ->middleware(
                'permiso:Configuracion,Roles,Ver'
            )
            ->name(
                'roles.index'
            );

        Route::post(
            '/',
            [
                RolController::class,
                'store',
            ]
        )
            ->middleware([
                'permiso:Configuracion,Roles,Crear',
                'throttle:sensitive',
            ])
            ->name(
                'roles.store'
            );

        Route::get(
            '/{rol}',
            [
                RolController::class,
                'show',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->middleware(
                'permiso:Configuracion,Roles,Ver'
            )
            ->name(
                'roles.show'
            );

        Route::put(
            '/{rol}',
            [
                RolController::class,
                'update',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->middleware([
                'permiso:Configuracion,Roles,Editar',
                'throttle:sensitive',
            ])
            ->name(
                'roles.update'
            );

        Route::patch(
            '/{rol}',
            [
                RolController::class,
                'update',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->middleware([
                'permiso:Configuracion,Roles,Editar',
                'throttle:sensitive',
            ]);

        Route::delete(
            '/{rol}',
            [
                RolController::class,
                'destroy',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->middleware([
                'permiso:Configuracion,Roles,Eliminar',
                'throttle:sensitive',
            ])
            ->name(
                'roles.destroy'
            );

        /*
        |--------------------------------------------------------------------------
        | Permisos del rol
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/{rol}/permisos',
            [
                RolController::class,
                'getPermisos',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->middleware(
                'permiso:Configuracion,Roles,Ver'
            )
            ->name(
                'roles.permissions'
            );

        Route::put(
            '/{rol}/permisos',
            [
                RolController::class,
                'syncPermisos',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->middleware([
                'permiso:Configuracion,Roles,Editar',
                'throttle:sensitive',
            ])
            ->name(
                'roles.permissions.sync'
            );

        /*
        |--------------------------------------------------------------------------
        | Módulos asignados al rol
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/{rol}/modulos',
            [
                ModuloRolController::class,
                'show',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->middleware(
                'permiso:Configuracion,Roles,Ver'
            )
            ->name(
                'roles.modules'
            );

        Route::post(
            '/{rol}/modulos',
            [
                ModuloRolController::class,
                'sync',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->middleware([
                'permiso:Configuracion,Roles,Editar',
                'throttle:sensitive',
            ])
            ->name(
                'roles.modules.sync'
            );

        Route::delete(
            '/{rol}/modulos/{modulo}',
            [
                ModuloRolController::class,
                'destroy',
            ]
        )
            ->whereNumber(
                'rol'
            )
            ->whereNumber(
                'modulo'
            )
            ->middleware([
                'permiso:Configuracion,Roles,Editar',
                'throttle:sensitive',
            ])
            ->name(
                'roles.modules.destroy'
            );
    });