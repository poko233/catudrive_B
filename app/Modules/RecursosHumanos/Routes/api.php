<?php

declare(strict_types=1);

use App\Modules\RecursosHumanos\Controllers\RecursosHumanosController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])
    ->prefix('recursos-humanos')
    ->controller(RecursosHumanosController::class)
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Ver
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/usuarios',
            'index'
        )
            ->middleware(
                'permiso:Recursos Humanos,Recursos Humanos,Ver'
            )
            ->name('recursos-humanos.usuarios.index');

        Route::get(
            '/usuarios/{id}',
            'show'
        )
            ->whereNumber('id')
            ->middleware(
                'permiso:Recursos Humanos,Recursos Humanos,Ver'
            )
            ->name('recursos-humanos.usuarios.show');

        /*
        |--------------------------------------------------------------------------
        | Editar
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/usuarios/{id}',
            'update'
        )
            ->whereNumber('id')
            ->middleware([
                'permiso:Recursos Humanos,Recursos Humanos,Editar',
                'throttle:write',
            ])
            ->name('recursos-humanos.usuarios.update');

        Route::patch(
            '/usuarios/{id}',
            'update'
        )
            ->whereNumber('id')
            ->middleware([
                'permiso:Recursos Humanos,Recursos Humanos,Editar',
                'throttle:write',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Fotografía
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/usuarios/{id}/foto',
            'updatePhoto'
        )
            ->whereNumber('id')
            ->middleware([
                'permiso:Recursos Humanos,Recursos Humanos,Editar',
                'throttle:uploads',
            ])
            ->name('recursos-humanos.usuarios.foto');
    });
