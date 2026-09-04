<?php

declare(strict_types=1);

use App\Modules\AsignacionVehiculo\Controllers\AsignacionVehiculoController;
use Illuminate\Support\Facades\Route;

Route::prefix(
    'asignaciones-vehiculos'
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
                    AsignacionVehiculoController::class,
                    'index',
                ]
            )
                ->middleware(
                    'permiso:Asignaciones,Asignacion Vehiculos,Ver'
                )
                ->name(
                    'asignaciones.vehiculos.index'
                );

            /*
            |--------------------------------------------------------------------------
            | CATÁLOGOS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/catalogos',
                [
                    AsignacionVehiculoController::class,
                    'catalogos',
                ]
            )
                ->middleware(
                    'permiso:Asignaciones,Asignacion Vehiculos,Ver'
                )
                ->name(
                    'asignaciones.vehiculos.catalogos'
                );

            /*
            |--------------------------------------------------------------------------
            | HISTORIAL
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/historial',
                [
                    AsignacionVehiculoController::class,
                    'historial',
                ]
            )
                ->middleware(
                    'permiso:Asignaciones,Asignacion Vehiculos,Ver'
                )
                ->name(
                    'asignaciones.vehiculos.historial'
                );

            /*
            |--------------------------------------------------------------------------
            | CREAR
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/',
                [
                    AsignacionVehiculoController::class,
                    'store',
                ]
            )
                ->middleware([
                    'permiso:Asignaciones,Asignacion Vehiculos,Crear',
                    'throttle:write',
                ])
                ->name(
                    'asignaciones.vehiculos.store'
                );

            /*
            |--------------------------------------------------------------------------
            | CAMBIO
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{asignacion}/cambiar',
                [
                    AsignacionVehiculoController::class,
                    'cambiar',
                ]
            )
                ->whereNumber(
                    'asignacion'
                )
                ->middleware([
                    'permiso:Asignaciones,Asignacion Vehiculos,Editar',
                    'throttle:write',
                ])
                ->name(
                    'asignaciones.vehiculos.cambiar'
                );

            /*
            |--------------------------------------------------------------------------
            | FINALIZAR
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{asignacion}/finalizar',
                [
                    AsignacionVehiculoController::class,
                    'finalizar',
                ]
            )
                ->whereNumber(
                    'asignacion'
                )
                ->middleware([
                    'permiso:Asignaciones,Asignacion Vehiculos,Editar',
                    'throttle:write',
                ])
                ->name(
                    'asignaciones.vehiculos.finalizar'
                );

            /*
            |--------------------------------------------------------------------------
            | DETALLE
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/{asignacion}',
                [
                    AsignacionVehiculoController::class,
                    'show',
                ]
            )
                ->whereNumber(
                    'asignacion'
                )
                ->middleware(
                    'permiso:Asignaciones,Asignacion Vehiculos,Ver'
                )
                ->name(
                    'asignaciones.vehiculos.show'
                );
        }
    );