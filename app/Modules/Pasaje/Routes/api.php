<?php

declare(strict_types=1);

use App\Modules\Pasaje\Controllers\PasajeReporteController;
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

        /*
        |--------------------------------------------------------------------------
        | VIAJES
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/viajes',
            [
                ViajeController::class,
                'index',
            ]
        )
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Pasajes,Ver'
            );

        /*
        |--------------------------------------------------------------------------
        | PRÓXIMA HORA DE VIAJE
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        |
        | GET /api/pasajes/viajes/proxima-hora/3
        |
        | Si la ruta comienza:
        |
        | 06:00
        |
        | Viaje 1 -> 06:00
        | Viaje 2 -> 06:30
        | Viaje 3 -> 07:00
        |
        */

        Route::get(
            '/viajes/proxima-hora/{ruta}',
            [
                ViajeController::class,
                'proximaHora',
            ]
        )
            ->whereNumber('ruta')
            ->middleware(
                'throttle:api',
                'permiso:Ventas,Pasajes,Ver'
            );

        /*
        |--------------------------------------------------------------------------
        | CREAR VIAJE
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/viajes',
            [
                ViajeController::class,
                'store',
            ]
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
        | GET /api/pasajes/viajes/{idViaje}/pasajeros
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
        | ENCOMIENDAS DE UN VIAJE
        |--------------------------------------------------------------------------
        |
        | GET /api/pasajes/viajes/{idViaje}/encomiendas
        |
        */

        Route::get(
            '/viajes/{idViaje}/encomiendas',
            [
                ViajeEncomiendaController::class,
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

                /*
                |--------------------------------------------------------------------------
                | REPORTE HTML
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | REPORTE PDF
                |--------------------------------------------------------------------------
                */

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
                | PLANILLA DE PASAJEROS HTML
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

                /*
                |--------------------------------------------------------------------------
                | PLANILLA DE PASAJEROS PDF
                |--------------------------------------------------------------------------
                */

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
        | CANCELAR VENTA
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
        | OBTENER VENTA
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
        | ELIMINAR DETALLE DE VENTA
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
        | RUTAS
        |--------------------------------------------------------------------------
        |
        | Búsqueda/listado de rutas dentro del módulo de Pasajes.
        |
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

                /*
                |--------------------------------------------------------------------------
                | LISTAR RELACIONES
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | CREAR RELACIÓN
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | DETALLE RELACIÓN
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR RELACIÓN
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | ELIMINAR RELACIÓN
                |--------------------------------------------------------------------------
                */

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