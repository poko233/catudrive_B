<?php

declare(strict_types=1);

use App\Modules\Pasaje\Controllers\PasajeReporteController;
use App\Modules\Pasaje\Controllers\RutaController;
use App\Modules\Pasaje\Controllers\VehiculoChoferRutaController;
use App\Modules\Pasaje\Controllers\VentaController;
use App\Modules\Pasaje\Controllers\ViajeController;
use App\Modules\Pasaje\Controllers\ViajePasajeroController;
use App\Shared\Middleware\CheckUserActive;
use Illuminate\Support\Facades\Route;

Route::prefix('pasajes')
    ->middleware([
        'auth:sanctum',
        CheckUserActive::class,
    ])
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | VIAJES
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/viajes',
            [ViajeController::class, 'index']
        )
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Pasajes,Ver'
            );

        Route::post(
            '/viajes',
            [ViajeController::class, 'store']
        )
            ->middleware(
                'throttle:write',
                'permiso:Ventas,Pasajes,Crear'
            );

        /*
        |--------------------------------------------------------------------------
        | PASAJEROS DE UN VIAJE
        |--------------------------------------------------------------------------
        |
        | GET
        | /api/pasajes/viajes/{idViaje}/pasajeros
        |
        */

        Route::get(
            '/viajes/{idViaje}/pasajeros',
            [
                ViajePasajeroController::class,
                'index',
            ]
        )
            ->whereNumber('idViaje')
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Pasajes,Ver'
            );

        /*
        |--------------------------------------------------------------------------
        | ASIENTOS DE UN VIAJE
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/viajes/{idViaje}/asientos',
            [
                VentaController::class,
                'obtenerAsientos',
            ]
        )
            ->whereNumber('idViaje')
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Pasajes,Ver'
            );

        /*
        |--------------------------------------------------------------------------
        | CAMBIAR ESTADO DEL VIAJE
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/viajes/{viaje}/estado',
            [
                ViajeController::class,
                'updateEstado',
            ]
        )
            ->whereNumber('viaje')
            ->middleware(
                'throttle:write',
                'permiso:Ventas,Pasajes,Editar'
            );

        /*
        |--------------------------------------------------------------------------
        | VENTAS
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/ventas/iniciar',
            [
                VentaController::class,
                'iniciarVenta',
            ]
        )
            ->middleware(
                'throttle:write',
                'permiso:Ventas,Pasajes,Crear'
            );

        /*
        |--------------------------------------------------------------------------
        | REPORTES
        |--------------------------------------------------------------------------
        */

        Route::prefix('reportes')
            ->middleware([
                'auth:sanctum',
                CheckUserActive::class,
            ])
            ->controller(PasajeReporteController::class)
            ->group(function (): void {

                Route::get(
                    '/{tipo}/html',
                    'html'
                )
                    ->whereIn('tipo', [
                        'vendidos_por_fecha',
                        'por_ruta',
                        'por_vehiculo',
                        'por_chofer',
                        'ingresos',
                    ])
                    ->middleware(
                        'throttle:api',
                        'permiso:Ventas,Pasajes,Ver'
                    )
                    ->name(
                        'pasajes.reportes.html'
                    );

                Route::get(
                    '/{tipo}/pdf',
                    'pdf'
                )
                    ->whereIn('tipo', [
                        'vendidos_por_fecha',
                        'por_ruta',
                        'por_vehiculo',
                        'por_chofer',
                        'ingresos',
                    ])
                    ->middleware(
                        'throttle:api',
                        'permiso:Ventas,Pasajes,Ver'
                    )
                    ->name(
                        'pasajes.reportes.pdf'
                    );

                /*
                |--------------------------------------------------------------------------
                | PLANILLA DE PASAJEROS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/planilla/{idViaje}/html',
                    'planillaHtml'
                )
                    ->whereNumber('idViaje')
                    ->middleware(
                        'throttle:api',
                        'permiso:Ventas,Pasajes,Ver'
                    )
                    ->name(
                        'pasajes.reportes.planilla.html'
                    );

                Route::get(
                    '/planilla/{idViaje}/pdf',
                    'planillaPdf'
                )
                    ->whereNumber('idViaje')
                    ->middleware(
                        'throttle:api',
                        'permiso:Ventas,Pasajes,Ver'
                    )
                    ->name(
                        'pasajes.reportes.planilla.pdf'
                    );
            });

        /*
        |--------------------------------------------------------------------------
        | CONFIRMAR VENTA
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/ventas/{ventaId}/confirmar',
            [
                VentaController::class,
                'confirmarVenta',
            ]
        )
            ->whereNumber('ventaId')
            ->middleware(
                'throttle:write',
                'permiso:Ventas,Pasajes,Editar'
            );

        /*
        |--------------------------------------------------------------------------
        | CANCELAR VENTA PENDIENTE
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/ventas/{ventaId}/cancelar',
            [
                VentaController::class,
                'cancelarVenta',
            ]
        )
            ->whereNumber('ventaId')
            ->middleware(
                'throttle:write',
                'permiso:Ventas,Pasajes,Editar'
            );

        /*
        |--------------------------------------------------------------------------
        | ANULAR VENTA
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/ventas/{ventaId}/anular',
            [
                VentaController::class,
                'anularVenta',
            ]
        )
            ->whereNumber('ventaId')
            ->middleware(
                'throttle:delete',
                'permiso:Ventas,Pasajes,Eliminar'
            );

        /*
        |--------------------------------------------------------------------------
        | DETALLE DE VENTA
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/ventas/{ventaId}',
            [
                VentaController::class,
                'show',
            ]
        )
            ->whereNumber('ventaId')
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Pasajes,Ver'
            );

        /*
        |--------------------------------------------------------------------------
        | TICKET HTML
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/ventas/{ventaId}/ticket-html',
            [
                VentaController::class,
                'ticketHtml',
            ]
        )
            ->whereNumber('ventaId')
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Pasajes,Ver'
            );

        /*
        |--------------------------------------------------------------------------
        | ELIMINAR DETALLE
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/detalles/{detalleId}',
            [
                VentaController::class,
                'eliminarDetalle',
            ]
        )
            ->whereNumber('detalleId')
            ->middleware(
                'throttle:delete',
                'permiso:Ventas,Pasajes,Eliminar'
            );

        /*
        |--------------------------------------------------------------------------
        | CAMBIAR ASIENTO
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/detalles/{detalleId}/cambiar-asiento',
            [
                VentaController::class,
                'cambiarAsiento',
            ]
        )
            ->whereNumber('detalleId')
            ->middleware(
                'throttle:write',
                'permiso:Ventas,Pasajes,Editar'
            );

        /*
        |--------------------------------------------------------------------------
        | PDF DE VENTA
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/ventas/{ventaId}/pdf',
            [
                VentaController::class,
                'generarPdf',
            ]
        )
            ->whereNumber('ventaId')
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Pasajes,Ver'
            );

        /*
        |--------------------------------------------------------------------------
        | BÚSQUEDA DE RUTAS
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/rutas',
            [
                RutaController::class,
                'index',
            ]
        )
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Transporte,Ver'
            );

        /*
        |--------------------------------------------------------------------------
        | VEHÍCULO - CHOFER - RUTA
        |--------------------------------------------------------------------------
        */

        Route::prefix(
            'vehiculo-chofer-ruta'
        )
            ->group(function (): void {

                Route::get(
                    '/',
                    [
                        VehiculoChoferRutaController::class,
                        'index',
                    ]
                )
                    ->middleware(
                        'throttle:api',
                        'permiso:Ventas,Transporte,Ver'
                    );

                Route::post(
                    '/',
                    [
                        VehiculoChoferRutaController::class,
                        'store',
                    ]
                )
                    ->middleware(
                        'throttle:write',
                        'permiso:Ventas,Transporte,Crear'
                    );

                Route::get(
                    '/{vcr}',
                    [
                        VehiculoChoferRutaController::class,
                        'show',
                    ]
                )
                    ->whereNumber('vcr')
                    ->middleware(
                        'throttle:api',
                        'permiso:Ventas,Transporte,Ver'
                    );

                Route::put(
                    '/{vcr}',
                    [
                        VehiculoChoferRutaController::class,
                        'update',
                    ]
                )
                    ->whereNumber('vcr')
                    ->middleware(
                        'throttle:write',
                        'permiso:Ventas,Transporte,Editar'
                    );

                Route::delete(
                    '/{vcr}',
                    [
                        VehiculoChoferRutaController::class,
                        'destroy',
                    ]
                )
                    ->whereNumber('vcr')
                    ->middleware(
                        'throttle:delete',
                        'permiso:Ventas,Transporte,Eliminar'
                    );
            });
    });