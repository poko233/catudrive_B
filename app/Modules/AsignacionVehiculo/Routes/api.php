<?php

declare(strict_types=1);

use App\Modules\AsignacionVehiculo\Controllers\AsignacionReporteController;
use App\Modules\AsignacionVehiculo\Controllers\AsignacionVehiculoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ASIGNACIONES DE VEHÍCULOS
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| REPORTES DE ASIGNACIONES
|--------------------------------------------------------------------------
|
| Mismo patrón de los reportes de Vehículos y Choferes.
|
*/

Route::prefix(
    'asignaciones-vehiculos/reportes'
)
    ->middleware([
        'auth:sanctum',
        'usuario.activo',
    ])
    ->controller(
        AsignacionReporteController::class
    )
    ->group(
        function (): void {
            Route::get(
                '/{tipo}/html',
                'html'
            )
                ->whereIn(
                    'tipo',
                    [
                        'por_chofer',
                        'historial',
                        'sin_asignar',
                    ]
                )
                ->middleware(
                    'throttle:api',
                    'permiso:Asignaciones,Reportes Asig.,Ver'
                )
                ->name(
                    'asignaciones.reportes.html'
                );

            Route::get(
                '/{tipo}/pdf',
                'pdf'
            )
                ->whereIn(
                    'tipo',
                    [
                        'por_chofer',
                        'historial',
                        'sin_asignar',
                    ]
                )
                ->middleware(
                    'throttle:api',
                    'permiso:Asignaciones,Reportes Asig.,Ver'
                )
                ->name(
                    'asignaciones.reportes.pdf'
                );
        }
    );
