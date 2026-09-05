<?php

declare(strict_types=1);

use App\Modules\Pasaje\Controllers\RutaController;
use App\Modules\Pasaje\Controllers\VehiculoChoferRutaController;
use App\Modules\Pasaje\Controllers\VentaController;
use App\Modules\Pasaje\Controllers\ViajeController;
use App\Shared\Middleware\CheckUserActive;
use Illuminate\Support\Facades\Route;

Route::prefix('pasajes')
    ->middleware(['auth:sanctum', CheckUserActive::class])
    ->group(function (): void {
        // Viajes
        Route::get('/viajes', [ViajeController::class, 'index'])
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::post('/viajes', [ViajeController::class, 'store'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Crear');

        // Asientos de un viaje
        Route::get('/viajes/{idViaje}/asientos', [VentaController::class, 'obtenerAsientos'])
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');
        Route::put('/viajes/{viaje}/estado', [ViajeController::class, 'updateEstado'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Editar');

        // Ventas
        Route::post('/ventas/iniciar', [VentaController::class, 'iniciarVenta'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Crear');

        Route::put('/ventas/{ventaId}/confirmar', [VentaController::class, 'confirmarVenta'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Editar');

        Route::post('/ventas/{ventaId}/cancelar', [VentaController::class, 'cancelarVenta'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Editar');

        Route::post('/ventas/{ventaId}/anular', [VentaController::class, 'anularVenta'])
            ->middleware('throttle:delete', 'permiso:Ventas,Pasajes,Eliminar');

        // Detalle venta
        Route::delete('/detalles/{detalleId}', [VentaController::class, 'eliminarDetalle'])
            ->middleware('throttle:delete', 'permiso:Ventas,Pasajes,Eliminar');

        Route::put('/detalles/{detalleId}/cambiar-asiento', [VentaController::class, 'cambiarAsiento'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Editar');

        // PDF
        Route::get('/ventas/{ventaId}/pdf', [VentaController::class, 'generarPdf'])
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');


        // Búsqueda de rutas (solo listado)
        Route::get('/rutas', [RutaController::class, 'index'])
            ->middleware('throttle:api', 'permiso:Ventas,Transporte,Ver');

        // Gestión de relaciones vehículo-chofer-ruta
        Route::prefix('vehiculo-chofer-ruta')->group(function () {
            Route::get('/', [VehiculoChoferRutaController::class, 'index'])
                ->middleware('throttle:api', 'permiso:Ventas,Transporte,Ver');
            Route::post('/', [VehiculoChoferRutaController::class, 'store'])
                ->middleware('throttle:write', 'permiso:Ventas,Transporte,Crear');
            Route::get('/{vcr}', [VehiculoChoferRutaController::class, 'show'])
                ->middleware('throttle:api', 'permiso:Ventas,Transporte,Ver');
            Route::put('/{vcr}', [VehiculoChoferRutaController::class, 'update'])
                ->middleware('throttle:write', 'permiso:Ventas,Transporte,Editar');
            Route::delete('/{vcr}', [VehiculoChoferRutaController::class, 'destroy'])
                ->middleware('throttle:delete', 'permiso:Ventas,Transporte,Eliminar');
        });
    });