<?php

declare(strict_types=1);

use App\Modules\Ruta\Controllers\RutaController;
use Illuminate\Support\Facades\Route;

Route::prefix(
    'rutas'
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
                    RutaController::class,
                    'index',
                ]
            )
                ->middleware(
                    'permiso:Rutas,Rutas,Ver'
                )
                ->name(
                    'rutas.index'
                );

            /*
            |--------------------------------------------------------------------------
            | CREAR
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/',
                [
                    RutaController::class,
                    'store',
                ]
            )
                ->middleware([
                    'permiso:Rutas,Rutas,Crear',
                    'throttle:write',
                ])
                ->name(
                    'rutas.store'
                );

            /*
            |--------------------------------------------------------------------------
            | CHOFERES CON VIAJES EN LA RUTA
            |--------------------------------------------------------------------------
            |
            | GET /api/rutas/{ruta}/choferes-viajes
            |
            */

            Route::get(
                '/{ruta}/choferes-viajes',
                [
                    RutaController::class,
                    'choferesViajes',
                ]
            )
                ->whereNumber(
                    'ruta'
                )
                ->middleware(
                    'permiso:Rutas,Rutas,Ver'
                )
                ->name(
                    'rutas.choferes-viajes'
                );

            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{ruta}',
                [
                    RutaController::class,
                    'update',
                ]
            )
                ->whereNumber(
                    'ruta'
                )
                ->middleware([
                    'permiso:Rutas,Rutas,Editar',
                    'throttle:write',
                ])
                ->name(
                    'rutas.update'
                );

            /*
            |--------------------------------------------------------------------------
            | BAJA
            |--------------------------------------------------------------------------
            */

            Route::delete(
                '/{ruta}',
                [
                    RutaController::class,
                    'destroy',
                ]
            )
                ->whereNumber(
                    'ruta'
                )
                ->middleware([
                    'permiso:Rutas,Rutas,Eliminar',
                    'throttle:write',
                ])
                ->name(
                    'rutas.destroy'
                );

            /*
            |--------------------------------------------------------------------------
            | DETALLE
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/{ruta}',
                [
                    RutaController::class,
                    'show',
                ]
            )
                ->whereNumber(
                    'ruta'
                )
                ->middleware(
                    'permiso:Rutas,Rutas,Ver'
                )
                ->name(
                    'rutas.show'
                );
        }
    );
