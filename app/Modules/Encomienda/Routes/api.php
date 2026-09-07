<?php

declare(strict_types=1);

use App\Modules\Encomienda\Controllers\EncomiendaController;
use Illuminate\Support\Facades\Route;

Route::prefix(
    'encomiendas'
)
    ->middleware([
        'auth:sanctum',
        'usuario.activo',
        'throttle:api',
    ])
    ->group(
        function (): void {
            /*
            |--------------------------------------------------------------------------
            | LISTAR
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/',
                [
                    EncomiendaController::class,
                    'index',
                ]
            )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.index'
                );

            /*
            |--------------------------------------------------------------------------
            | CATÁLOGOS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/catalogos',
                [
                    EncomiendaController::class,
                    'catalogos',
                ]
            )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.catalogos'
                );

            /*
            |--------------------------------------------------------------------------
            | BUSCAR POR GUÍA
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/guia/{guia}',
                [
                    EncomiendaController::class,
                    'buscarPorGuia',
                ]
            )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.guia'
                );

            /*
            |--------------------------------------------------------------------------
            | CREAR
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/',
                [
                    EncomiendaController::class,
                    'store',
                ]
            )
                ->middleware([
                    'permiso:Encomiendas,Encomiendas,Crear',
                    'throttle:write',
                ])
                ->name(
                    'encomiendas.store'
                );

            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{encomienda}',
                [
                    EncomiendaController::class,
                    'update',
                ]
            )
                ->whereNumber(
                    'encomienda'
                )
                ->middleware([
                    'permiso:Encomiendas,Encomiendas,Editar',
                    'throttle:write',
                ])
                ->name(
                    'encomiendas.update'
                );

            /*
            |--------------------------------------------------------------------------
            | ASIGNAR
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{encomienda}/asignar',
                [
                    EncomiendaController::class,
                    'asignar',
                ]
            )
                ->whereNumber(
                    'encomienda'
                )
                ->middleware([
                    'permiso:Encomiendas,Encomiendas,Editar',
                    'throttle:write',
                ])
                ->name(
                    'encomiendas.asignar'
                );

            /*
            |--------------------------------------------------------------------------
            | ENTREGAR
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{encomienda}/entregar',
                [
                    EncomiendaController::class,
                    'entregar',
                ]
            )
                ->whereNumber(
                    'encomienda'
                )
                ->middleware([
                    'permiso:Encomiendas,Encomiendas,Editar',
                    'throttle:write',
                ])
                ->name(
                    'encomiendas.entregar'
                );

            /*
            |--------------------------------------------------------------------------
            | ANULAR
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{encomienda}/anular',
                [
                    EncomiendaController::class,
                    'anular',
                ]
            )
                ->whereNumber(
                    'encomienda'
                )
                ->middleware([
                    'permiso:Encomiendas,Encomiendas,Editar',
                    'throttle:write',
                ])
                ->name(
                    'encomiendas.anular'
                );

            /*
            |--------------------------------------------------------------------------
            | DETALLE
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/{encomienda}',
                [
                    EncomiendaController::class,
                    'show',
                ]
            )
                ->whereNumber(
                    'encomienda'
                )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.show'
                );
        }
    );