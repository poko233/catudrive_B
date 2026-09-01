<?php

declare(strict_types=1);

use App\Modules\Modulo\Controllers\FormularioModuloController;
use App\Modules\Modulo\Controllers\ModuloController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Listado masivo Formulario ↔ Módulo
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | Esta ruta debe estar antes de:
    |
    | /modulos/{modulo}
    |
    | para evitar que Laravel interprete
    | "formularios" como si fuera el ID del módulo.
    |
    */

    Route::get(
        'modulos/formularios/asignaciones',
        [
            FormularioModuloController::class,
            'index',
        ]
    )
        ->middleware(
            'permiso:Configuracion,Modulos,Ver'
        )
        ->name(
            'modulos.formularios.index'
        );

    /*
    |--------------------------------------------------------------------------
    | CRUD de Módulos
    |--------------------------------------------------------------------------
    */

    Route::prefix(
        'modulos'
    )
        ->controller(
            ModuloController::class
        )
        ->group(function (): void {

            /*
            |--------------------------------------------------------------------------
            | Listar
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/',
                'index'
            )
                ->middleware(
                    'permiso:Configuracion,Modulos,Ver'
                )
                ->name(
                    'modulos.index'
                );

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
                    'permiso:Configuracion,Modulos,Crear',
                    'throttle:write',
                ])
                ->name(
                    'modulos.store'
                );

            /*
            |--------------------------------------------------------------------------
            | Ordenar Sidebar
            |--------------------------------------------------------------------------
            |
            | MUY IMPORTANTE:
            |
            | Debe estar ANTES de:
            |
            | /{modulo}
            |
            | porque si no Laravel podría intentar interpretar:
            |
            | /modulos/orden
            |
            | como:
            |
            | modulo = "orden"
            |
            */

            Route::put(
                '/orden',
                'reorder'
            )
                ->middleware([
                    'permiso:Configuracion,Modulos,Editar',
                    'throttle:write',
                ])
                ->name(
                    'modulos.orden'
                );

            /*
            |--------------------------------------------------------------------------
            | Ver detalle
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/{modulo}',
                'show'
            )
                ->whereNumber(
                    'modulo'
                )
                ->middleware(
                    'permiso:Configuracion,Modulos,Ver'
                )
                ->name(
                    'modulos.show'
                );

            /*
            |--------------------------------------------------------------------------
            | Actualizar
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{modulo}',
                'update'
            )
                ->whereNumber(
                    'modulo'
                )
                ->middleware([
                    'permiso:Configuracion,Modulos,Editar',
                    'throttle:write',
                ])
                ->name(
                    'modulos.update'
                );

            Route::patch(
                '/{modulo}',
                'update'
            )
                ->whereNumber(
                    'modulo'
                )
                ->middleware([
                    'permiso:Configuracion,Modulos,Editar',
                    'throttle:write',
                ])
                ->name(
                    'modulos.patch'
                );

            /*
            |--------------------------------------------------------------------------
            | Eliminar
            |--------------------------------------------------------------------------
            */

            Route::delete(
                '/{modulo}',
                'destroy'
            )
                ->whereNumber(
                    'modulo'
                )
                ->middleware([
                    'permiso:Configuracion,Modulos,Eliminar',
                    'throttle:delete',
                ])
                ->name(
                    'modulos.destroy'
                );
        });

    /*
    |--------------------------------------------------------------------------
    | Formularios asignados a Módulos
    |--------------------------------------------------------------------------
    */

    Route::prefix(
        'modulos/{modulo}/formularios'
    )
        ->whereNumber(
            'modulo'
        )
        ->controller(
            FormularioModuloController::class
        )
        ->group(function (): void {

            /*
            |--------------------------------------------------------------------------
            | Ver formularios
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/',
                'show'
            )
                ->middleware(
                    'permiso:Configuracion,Modulos,Ver'
                )
                ->name(
                    'modulos.formularios.show'
                );

            /*
            |--------------------------------------------------------------------------
            | Sincronizar formularios
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/',
                'sync'
            )
                ->middleware([
                    'permiso:Configuracion,Modulos,Editar',
                    'throttle:write',
                ])
                ->name(
                    'modulos.formularios.sync'
                );

            /*
            |--------------------------------------------------------------------------
            | Desasignar formulario
            |--------------------------------------------------------------------------
            */

            Route::delete(
                '/{formulario}',
                'destroy'
            )
                ->whereNumber(
                    'formulario'
                )
                ->middleware([
                    'permiso:Configuracion,Modulos,Editar',
                    'throttle:delete',
                ])
                ->name(
                    'modulos.formularios.destroy'
                );
        });
});