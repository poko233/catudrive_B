<?php

declare(strict_types=1);

use App\Modules\Vehiculo\Controllers\CategoriaVehiculoController;
use App\Modules\Vehiculo\Controllers\VehiculoController;
use App\Modules\Vehiculo\Controllers\VehiculoReporteController;
use App\Shared\Middleware\CheckUserActive;
use Illuminate\Support\Facades\Route;

Route::prefix('vehiculos')
    ->middleware(['auth:sanctum', CheckUserActive::class])
    ->group(function (): void {
        // Rutas existentes de vehículos...
        Route::get('/', [VehiculoController::class, 'index'])
            ->middleware('throttle:api', 'permiso:Vehiculo,Vehiculo,Ver');

        Route::post('/', [VehiculoController::class, 'store'])
            ->middleware('throttle:write', 'permission:Vehiculo,Vehiculo,Crear');

        Route::get('/{vehiculo}', [VehiculoController::class, 'show'])
            ->middleware('throttle:api', 'permission:Vehiculo,Vehiculo,Ver');

        Route::put('/{vehiculo}', [VehiculoController::class, 'update'])
            ->middleware('throttle:write', 'permission:Vehiculo,Vehiculo,Editar');

        Route::delete('/{vehiculo}', [VehiculoController::class, 'destroy'])
            ->middleware('throttle:delete', 'permission:Vehiculo,Vehiculo,Eliminar');
    });

// CRUD de categorías de vehículo
Route::prefix('categorias-vehiculo')
    ->middleware(['auth:sanctum', CheckUserActive::class])
    ->group(function (): void {
        Route::get('/', [CategoriaVehiculoController::class, 'index'])
            ->middleware('throttle:api', 'permission:Vehiculo,CategoriaVehiculo,Ver');

        Route::post('/', [CategoriaVehiculoController::class, 'store'])
            ->middleware('throttle:write', 'permission:Vehiculo,CategoriaVehiculo,Crear');

        Route::get('/{categoria_vehiculo}', [CategoriaVehiculoController::class, 'show'])
            ->middleware('throttle:api', 'permission:Vehiculo,CategoriaVehiculo,Ver');

        Route::put('/{categoria_vehiculo}', [CategoriaVehiculoController::class, 'update'])
            ->middleware('throttle:write', 'permission:Vehiculo,CategoriaVehiculo,Editar');

        Route::delete('/{categoria_vehiculo}', [CategoriaVehiculoController::class, 'destroy'])
            ->middleware('throttle:delete', 'permission:Vehiculo,CategoriaVehiculo,Eliminar');
    });

Route::prefix('vehiculos/reportes')
    ->middleware(['auth:sanctum', CheckUserActive::class])
    ->controller(VehiculoReporteController::class)
    ->group(function (): void {
        Route::get('/{tipo}/html', 'html')
            ->whereIn('tipo', ['lista', 'disponibles', 'asignados', 'por_propietario'])
            ->middleware('throttle:api', 'permiso:Vehiculos,Reportes,Ver')
            ->name('vehiculos.reportes.html');

        Route::get('/{tipo}/pdf', 'pdf')
            ->whereIn('tipo', ['lista', 'disponibles', 'asignados', 'por_propietario'])
            ->middleware('throttle:api', 'permiso:Vehiculos,Reportes,Ver')
            ->name('vehiculos.reportes.pdf');
    });