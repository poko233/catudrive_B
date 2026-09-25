<?php

declare(strict_types=1);

use App\Modules\Pasaje\Controllers\PasajeReporteController;
use App\Modules\Pasaje\Controllers\PasajeroController;
use App\Modules\Pasaje\Controllers\RutaController;
use App\Modules\Pasaje\Controllers\VehiculoChoferRutaController;
use App\Modules\Pasaje\Controllers\VentaController;
use App\Modules\Pasaje\Controllers\ViajeController;
use App\Modules\Pasaje\Controllers\ViajePasajeroController;
use App\Modules\Pasaje\Controllers\ViajeEncomiendaController;
use App\Shared\Middleware\CheckUserActive;
use Illuminate\Support\Facades\Route;

Route::prefix('pasajes')
    ->middleware([
        'auth:sanctum',
        CheckUserActive::class,
    ])
    ->group(function (): void {

        Route::get('/viajes', [ViajeController::class, 'index'])
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::get('/viajes/proxima-hora/{ruta}', [ViajeController::class, 'proximaHora'])
            ->whereNumber('ruta')
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::post('/viajes', [ViajeController::class, 'store'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Crear');

        Route::get('/viajes/{idViaje}/pasajeros', [ViajePasajeroController::class, 'index'])
            ->whereNumber('idViaje')
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::get('/viajes/{idViaje}/encomiendas', [ViajeEncomiendaController::class, 'index'])
            ->whereNumber('idViaje')
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::get('/viajes/{idViaje}/asientos', [VentaController::class, 'obtenerAsientos'])
            ->whereNumber('idViaje')
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::put('/viajes/{viaje}/estado', [ViajeController::class, 'updateEstado'])
            ->whereNumber('viaje')
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Editar');

        /*
        |--------------------------------------------------------------------------
        | PASAJEROS RECURRENTES
        |--------------------------------------------------------------------------
        */

        Route::get('/pasajeros', [PasajeroController::class, 'index'])
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::post('/pasajeros', [PasajeroController::class, 'store'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Crear');

        Route::post('/ventas/iniciar', [VentaController::class, 'iniciarVenta'])
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Crear');

        Route::prefix('reportes')
            ->middleware([
                'auth:sanctum',
                CheckUserActive::class,
            ])
            ->controller(PasajeReporteController::class)
            ->group(function (): void {

                Route::get('/{tipo}/html', 'html')
                    ->whereIn('tipo', [
                        'vendidos_por_fecha',
                        'por_ruta',
                        'por_vehiculo',
                        'por_chofer',
                        'ingresos',
                    ])
                    ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver')
                    ->name('pasajes.reportes.html');

                Route::get('/{tipo}/pdf', 'pdf')
                    ->whereIn('tipo', [
                        'vendidos_por_fecha',
                        'por_ruta',
                        'por_vehiculo',
                        'por_chofer',
                        'ingresos',
                    ])
                    ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver')
                    ->name('pasajes.reportes.pdf');

                Route::get('/planilla/{idViaje}/html', 'planillaHtml')
                    ->whereNumber('idViaje')
                    ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver')
                    ->name('pasajes.reportes.planilla.html');

                Route::get('/planilla/{idViaje}/pdf', 'planillaPdf')
                    ->whereNumber('idViaje')
                    ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver')
                    ->name('pasajes.reportes.planilla.pdf');
            });

        Route::put('/ventas/{ventaId}/confirmar', [VentaController::class, 'confirmarVenta'])
            ->whereNumber('ventaId')
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Editar');

        Route::post('/ventas/{ventaId}/cancelar', [VentaController::class, 'cancelarVenta'])
            ->whereNumber('ventaId')
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Editar');

        Route::post('/ventas/{ventaId}/anular', [VentaController::class, 'anularVenta'])
            ->whereNumber('ventaId')
            ->middleware('throttle:delete', 'permiso:Ventas,Pasajes,Eliminar');

        Route::get('/ventas/{ventaId}', [VentaController::class, 'show'])
            ->whereNumber('ventaId')
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::get('/ventas/{ventaId}/ticket-html', [VentaController::class, 'ticketHtml'])
            ->whereNumber('ventaId')
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::delete('/detalles/{detalleId}', [VentaController::class, 'eliminarDetalle'])
            ->whereNumber('detalleId')
            ->middleware('throttle:delete', 'permiso:Ventas,Pasajes,Eliminar');

        Route::put('/detalles/{detalleId}/cambiar-asiento', [VentaController::class, 'cambiarAsiento'])
            ->whereNumber('detalleId')
            ->middleware('throttle:write', 'permiso:Ventas,Pasajes,Editar');

        Route::get('/ventas/{ventaId}/pdf', [VentaController::class, 'generarPdf'])
            ->whereNumber('ventaId')
            ->middleware('throttle:api', 'permiso:Ventas,Pasajes,Ver');

        Route::get('/rutas', [RutaController::class, 'index'])
            ->middleware('throttle:api', 'permiso:Ventas,Transporte,Ver');

        Route::prefix('vehiculo-chofer-ruta')
            ->group(function (): void {

                Route::get('/', [VehiculoChoferRutaController::class, 'index'])
                    ->middleware('throttle:api', 'permiso:Ventas,Transporte,Ver');

                Route::post('/', [VehiculoChoferRutaController::class, 'store'])
                    ->middleware('throttle:write', 'permiso:Ventas,Transporte,Crear');

                Route::get('/{vcr}', [VehiculoChoferRutaController::class, 'show'])
                    ->whereNumber('vcr')
                    ->middleware('throttle:api', 'permiso:Ventas,Transporte,Ver');

                Route::put('/{vcr}', [VehiculoChoferRutaController::class, 'update'])
                    ->whereNumber('vcr')
                    ->middleware('throttle:write', 'permiso:Ventas,Transporte,Editar');

                Route::delete('/{vcr}', [VehiculoChoferRutaController::class, 'destroy'])
                    ->whereNumber('vcr')
                    ->middleware('throttle:delete', 'permiso:Ventas,Transporte,Eliminar');
            });
    });
