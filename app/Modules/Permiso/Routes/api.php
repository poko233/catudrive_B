<?php

declare(strict_types=1);

use App\Modules\Permiso\Controllers\FormularioAccionController;
use App\Modules\Permiso\Controllers\PermisoController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Permisos RBAC
    |--------------------------------------------------------------------------
    |
    | Es configuración global y sensible; no depende de una sucursal.
    |
    */

    Route::prefix('permisos')
        ->controller(PermisoController::class)
        ->group(function (): void {

            Route::get('/{idRol}', 'index')
                ->whereNumber('idRol')
                ->middleware(
                    'permiso:Configuracion,Roles,Ver'
                )
                ->name('permisos.index');

            Route::post('/{idRol}', 'addPermiso')
                ->whereNumber('idRol')
                ->middleware([
                    'permiso:Configuracion,Roles,Editar',
                    'throttle:sensitive',
                ])
                ->name('permisos.store');

            Route::post('/{idRol}/sync', 'sync')
                ->whereNumber('idRol')
                ->middleware([
                    'permiso:Configuracion,Roles,Editar',
                    'throttle:sensitive',
                ])
                ->name('permisos.sync');

            Route::delete(
                '/{rolId}/{formularioId}/{accionId}',
                'destroy'
            )
                ->whereNumber('rolId')
                ->whereNumber('formularioId')
                ->whereNumber('accionId')
                ->middleware([
                    'permiso:Configuracion,Roles,Editar',
                    'throttle:sensitive',
                ])
                ->name('permisos.destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Reglas de Visibility
    |--------------------------------------------------------------------------
    |
    | Antes estas rutas solo exigían autenticación. Ahora también
    | requieren permisos reales sobre Roles.
    |
    */

    Route::prefix('formulario_acciones')
        ->controller(FormularioAccionController::class)
        ->group(function (): void {

            Route::get('/', 'index')
                ->middleware(
                    'permiso:Configuracion,Roles,Ver'
                )
                ->name('formulario-acciones.index');

            Route::post('/', 'store')
                ->middleware([
                    'permiso:Configuracion,Roles,Editar',
                    'throttle:sensitive',
                ])
                ->name('formulario-acciones.store');

            Route::patch(
                '/{formularioAccion}',
                'update'
            )
                ->whereNumber('formularioAccion')
                ->middleware([
                    'permiso:Configuracion,Roles,Editar',
                    'throttle:sensitive',
                ])
                ->name('formulario-acciones.update');

            Route::delete(
                '/{formularioAccion}',
                'destroy'
            )
                ->whereNumber('formularioAccion')
                ->middleware([
                    'permiso:Configuracion,Roles,Editar',
                    'throttle:sensitive',
                ])
                ->name('formulario-acciones.destroy');
        });
});
