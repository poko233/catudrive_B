<?php

declare(strict_types=1);

use App\Modules\Formulario\Controllers\FormularioController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])
    ->prefix('formularios')
    ->controller(FormularioController::class)
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Ver
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/',
            'index'
        )
            ->middleware(
                'permiso:Configuracion,Formularios,Ver'
            )
            ->name('formularios.index');

        Route::get(
            '/{formulario}',
            'show'
        )
            ->whereNumber('formulario')
            ->middleware(
                'permiso:Configuracion,Formularios,Ver'
            )
            ->name('formularios.show');

        /*
        |--------------------------------------------------------------------------
        | Crear
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/',
            'store'
        )
            ->middleware([
                'permiso:Configuracion,Formularios,Crear',
                'throttle:write',
            ])
            ->name('formularios.store');

        /*
        |--------------------------------------------------------------------------
        | Editar
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/{formulario}',
            'update'
        )
            ->whereNumber('formulario')
            ->middleware([
                'permiso:Configuracion,Formularios,Editar',
                'throttle:write',
            ])
            ->name('formularios.update');

        Route::patch(
            '/{formulario}',
            'update'
        )
            ->whereNumber('formulario')
            ->middleware([
                'permiso:Configuracion,Formularios,Editar',
                'throttle:write',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Eliminar
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/{formulario}',
            'destroy'
        )
            ->whereNumber('formulario')
            ->middleware([
                'permiso:Configuracion,Formularios,Eliminar',
                'throttle:delete',
            ])
            ->name('formularios.destroy');
    });
