<?php

declare(strict_types=1);

use App\Modules\Arqueo\Controllers\ArqueoController;
use App\Modules\Arqueo\Controllers\EgresoController;
use App\Modules\Arqueo\Controllers\IngresoController;
use App\Modules\Arqueo\Controllers\TipoTransaccionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'usuario.activo'])->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Catálogo de tipos de transacción
    |--------------------------------------------------------------------------
    */

    Route::prefix('tipos-transaccion')->group(function (): void {
        Route::get('/', [TipoTransaccionController::class, 'index'])
            ->middleware('permiso:Arqueo,TipoTransaccion,Ver');

        Route::post('/', [TipoTransaccionController::class, 'store'])
            ->middleware('permiso:Arqueo,TipoTransaccion,Crear');

        Route::get('/{tipoTransaccion}', [TipoTransaccionController::class, 'show'])
            ->middleware('permiso:Arqueo,TipoTransaccion,Ver');

        Route::put('/{tipoTransaccion}', [TipoTransaccionController::class, 'update'])
            ->middleware('permiso:Arqueo,TipoTransaccion,Editar');

        Route::delete('/{tipoTransaccion}', [TipoTransaccionController::class, 'destroy'])
            ->middleware('permiso:Arqueo,TipoTransaccion,Eliminar');
    });

    /*
    |--------------------------------------------------------------------------
    | Arqueos
    |--------------------------------------------------------------------------
    |
    | El orden importa: /abierto va antes de /{arqueo} para que el
    | route model binding no intente resolver "abierto" como id.
    |
    */

    Route::prefix('arqueos')->group(function (): void {
        Route::get('/', [ArqueoController::class, 'index'])
            ->middleware('permiso:Arqueo,Arqueo,Ver');

        Route::get('/abierto', [ArqueoController::class, 'abierto'])
            ->middleware('permiso:Arqueo,Arqueo,Ver');

        Route::post('/abrir', [ArqueoController::class, 'abrir'])
            ->middleware('permiso:Arqueo,Arqueo,Crear');

        Route::get('/{arqueo}', [ArqueoController::class, 'show'])
            ->middleware('permiso:Arqueo,Arqueo,Ver');

        Route::patch('/{arqueo}/cerrar', [ArqueoController::class, 'cerrar'])
            ->middleware('permiso:Arqueo,Arqueo,Editar');

        Route::delete('/{arqueo}', [ArqueoController::class, 'destroy'])
            ->middleware('permiso:Arqueo,Arqueo,Eliminar');
    });

    /*
    |--------------------------------------------------------------------------
    | Ingresos
    |--------------------------------------------------------------------------
    */

    Route::prefix('ingresos')->group(function (): void {
        Route::get('/', [IngresoController::class, 'index'])
            ->middleware('permiso:Arqueo,Ingreso,Ver');

        Route::post('/', [IngresoController::class, 'store'])
            ->middleware('permiso:Arqueo,Ingreso,Crear');

        Route::get('/{ingreso}', [IngresoController::class, 'show'])
            ->middleware('permiso:Arqueo,Ingreso,Ver');

        Route::post('/{ingreso}/anular', [IngresoController::class, 'anular'])
            ->middleware('permiso:Arqueo,Ingreso,Editar');

        Route::get('/{ingreso}/comprobante', [IngresoController::class, 'comprobante'])
            ->middleware('permiso:Arqueo,Ingreso,Ver');
    });

    /*
    |--------------------------------------------------------------------------
    | Egresos
    |--------------------------------------------------------------------------
    */

    Route::prefix('egresos')->group(function (): void {
        Route::get('/', [EgresoController::class, 'index'])
            ->middleware('permiso:Arqueo,Egreso,Ver');

        Route::post('/', [EgresoController::class, 'store'])
            ->middleware('permiso:Arqueo,Egreso,Crear');

        Route::get('/{egreso}', [EgresoController::class, 'show'])
            ->middleware('permiso:Arqueo,Egreso,Ver');

        Route::post('/{egreso}/anular', [EgresoController::class, 'anular'])
            ->middleware('permiso:Arqueo,Egreso,Editar');

        Route::get('/{egreso}/comprobante', [EgresoController::class, 'comprobante'])
            ->middleware('permiso:Arqueo,Egreso,Ver');
    });
});