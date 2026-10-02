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
        Route::get('/', [TipoTransaccionController::class, 'index']);

        Route::post('/', [TipoTransaccionController::class, 'store']);

        Route::get('/{tipoTransaccion}', [TipoTransaccionController::class, 'show']);

        Route::put('/{tipoTransaccion}', [TipoTransaccionController::class, 'update']);

        Route::delete('/{tipoTransaccion}', [TipoTransaccionController::class, 'destroy']);
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
        Route::get('/', [ArqueoController::class, 'index']);

        Route::get('/abierto', [ArqueoController::class, 'abierto']);

        Route::post('/abrir', [ArqueoController::class, 'abrir']);

        Route::get('/{arqueo}', [ArqueoController::class, 'show']);

        Route::patch('/{arqueo}/cerrar', [ArqueoController::class, 'cerrar']);

        Route::delete('/{arqueo}', [ArqueoController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Ingresos
    |--------------------------------------------------------------------------
    */

    Route::prefix('ingresos')->group(function (): void {
        Route::get('/', [IngresoController::class, 'index']);

        Route::post('/', [IngresoController::class, 'store']);

        Route::get('/{ingreso}', [IngresoController::class, 'show']);

        Route::post('/{ingreso}/anular', [IngresoController::class, 'anular']);

        Route::get('/{ingreso}/comprobante', [IngresoController::class, 'comprobante']);
    });

    /*
    |--------------------------------------------------------------------------
    | Egresos
    |--------------------------------------------------------------------------
    */

    Route::prefix('egresos')->group(function (): void {
        Route::get('/', [EgresoController::class, 'index']);

        Route::post('/', [EgresoController::class, 'store']);

        Route::get('/{egreso}', [EgresoController::class, 'show']);

        Route::post('/{egreso}/anular', [EgresoController::class, 'anular']);

        Route::get('/{egreso}/comprobante', [EgresoController::class, 'comprobante']);
    });
});