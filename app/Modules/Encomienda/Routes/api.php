<?php

declare(strict_types=1);

use App\Modules\Encomienda\Controllers\EncomiendaController;
use App\Modules\Encomienda\Controllers\EncomiendaReporteController;
use App\Modules\Encomienda\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;


Route::prefix('clientes')->middleware(['auth:sanctum','usuario.activo','throttle:api','permiso:Encomiendas,Encomiendas,Ver'])->group(function (): void {
    Route::get('/', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/', [ClienteController::class, 'store'])->middleware(['permiso:Encomiendas,Encomiendas,Crear','throttle:write'])->name('clientes.store');
    Route::put('/{cliente}', [ClienteController::class, 'update'])->whereNumber('cliente')->middleware(['permiso:Encomiendas,Encomiendas,Editar','throttle:write'])->name('clientes.update');
    Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->whereNumber('cliente')->middleware(['permiso:Encomiendas,Encomiendas,Eliminar','throttle:write'])->name('clientes.destroy');
});

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
            | CAMBIAR ESTADO LOGÍSTICO
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/{encomienda}/estado',
                [EncomiendaController::class, 'cambiarEstado']
            )
                ->whereNumber('encomienda')
                ->middleware([
                    'permiso:Encomiendas,Encomiendas,Editar',
                    'throttle:write',
                ])
                ->name('encomiendas.estado');

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
            | QR - OBTENER
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/{encomienda}/qr',
                [
                    EncomiendaController::class,
                    'qr',
                ]
            )
                ->whereNumber('encomienda')
                ->middleware('permiso:Encomiendas,Encomiendas,Ver')
                ->name('encomiendas.qr');

            /*
            |--------------------------------------------------------------------------
            | QR - ESCANEAR
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/qr/escanear',
                [
                    EncomiendaController::class,
                    'escanearQr',
                ]
            )
                ->middleware([
                    'permiso:Encomiendas,Encomiendas,Ver',
                    'throttle:write',
                ])
                ->name('encomiendas.qr.escanear');

            /*
            |--------------------------------------------------------------------------
            | QR - TICKET / COMPROBANTE
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/{encomienda}/qr/ticket-html',
                [
                    EncomiendaController::class,
                    'ticketQr',
                ]
            )
                ->whereNumber('encomienda')
                ->middleware('permiso:Encomiendas,Encomiendas,Ver')
                ->name('encomiendas.qr.ticket');

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
            | REPORTES - REGISTRADAS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/reportes/registradas',
                [
                    EncomiendaReporteController::class,
                    'registradas',
                ]
            )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.reportes.registradas'
                );

            /*
            |--------------------------------------------------------------------------
            | REPORTES - PENDIENTES
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/reportes/pendientes',
                [
                    EncomiendaReporteController::class,
                    'pendientes',
                ]
            )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.reportes.pendientes'
                );

            /*
            |--------------------------------------------------------------------------
            | REPORTES - ENTREGADAS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/reportes/entregadas',
                [
                    EncomiendaReporteController::class,
                    'entregadas',
                ]
            )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.reportes.entregadas'
                );

            /*
            |--------------------------------------------------------------------------
            | REPORTES - POR DESTINO
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/reportes/por-destino',
                [
                    EncomiendaReporteController::class,
                    'porDestino',
                ]
            )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.reportes.por-destino'
                );

            /*
            |--------------------------------------------------------------------------
            | REPORTES - INGRESOS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/reportes/ingresos',
                [
                    EncomiendaReporteController::class,
                    'ingresos',
                ]
            )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.reportes.ingresos'
                );

            /*
            |--------------------------------------------------------------------------
            | REPORTES - HTML PARA IMPRESIÓN
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/reportes/{tipo}/html',
                [
                    EncomiendaReporteController::class,
                    'html',
                ]
            )
                ->whereIn(
                    'tipo',
                    [
                        'registradas',
                        'pendientes',
                        'entregadas',
                        'por_destino',
                        'ingresos',
                    ]
                )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.reportes.html'
                );

            /*
            |--------------------------------------------------------------------------
            | REPORTES - PDF
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/reportes/{tipo}/pdf',
                [
                    EncomiendaReporteController::class,
                    'pdf',
                ]
            )
                ->whereIn(
                    'tipo',
                    [
                        'registradas',
                        'pendientes',
                        'entregadas',
                        'por_destino',
                        'ingresos',
                    ]
                )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.reportes.pdf'
                );

            /*
            |--------------------------------------------------------------------------
            | REPORTES - CSV
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/reportes/{tipo}/csv',
                [
                    EncomiendaReporteController::class,
                    'csv',
                ]
            )
                ->whereIn(
                    'tipo',
                    [
                        'registradas',
                        'pendientes',
                        'entregadas',
                        'por_destino',
                        'ingresos',
                    ]
                )
                ->middleware(
                    'permiso:Encomiendas,Encomiendas,Ver'
                )
                ->name(
                    'encomiendas.reportes.csv'
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