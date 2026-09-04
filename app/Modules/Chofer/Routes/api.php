<?php

declare(strict_types=1);

use App\Modules\Chofer\Controllers\ChoferController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])
    ->prefix('choferes')
    ->controller(
        ChoferController::class
    )
    ->group(function (): void {

        Route::get(
            '/',
            'index'
        )
            ->middleware(
                'permiso:Choferes,Choferes,Ver'
            )
            ->name(
                'choferes.index'
            );

        Route::post(
            '/',
            'store'
        )
            ->middleware([
                'permiso:Choferes,Choferes,Crear',
                'throttle:write',
            ])
            ->name(
                'choferes.store'
            );

        Route::get(
            '/{chofer}/historial',
            'historial'
        )
            ->whereNumber(
                'chofer'
            )
            ->middleware(
                'permiso:Choferes,Choferes,Ver'
            )
            ->name(
                'choferes.historial'
            );

        Route::post(
            '/{chofer}/foto',
            'updatePhoto'
        )
            ->whereNumber(
                'chofer'
            )
            ->middleware([
                'permiso:Choferes,Choferes,Editar',
                'throttle:uploads',
            ])
            ->name(
                'choferes.foto'
            );

        Route::post(
            '/{chofer}/qr/regenerar',
            'regenerarQr'
        )
            ->whereNumber(
                'chofer'
            )
            ->middleware([
                'permiso:Choferes,Choferes,Editar',
                'throttle:write',
            ])
            ->name(
                'choferes.qr.regenerar'
            );

        Route::get(
            '/{chofer}',
            'show'
        )
            ->whereNumber(
                'chofer'
            )
            ->middleware(
                'permiso:Choferes,Choferes,Ver'
            )
            ->name(
                'choferes.show'
            );

        Route::put(
            '/{chofer}',
            'update'
        )
            ->whereNumber(
                'chofer'
            )
            ->middleware([
                'permiso:Choferes,Choferes,Editar',
                'throttle:write',
            ])
            ->name(
                'choferes.update'
            );

        Route::delete(
            '/{chofer}',
            'destroy'
        )
            ->whereNumber(
                'chofer'
            )
            ->middleware([
                'permiso:Choferes,Choferes,Eliminar',
                'throttle:delete',
            ])
            ->name(
                'choferes.destroy'
            );
        Route::get(
            '/search',
            'search'
        )
            ->middleware(
                'permiso:Choferes,Choferes,Ver'
            )
            ->name(
                'choferes.search'
            );
    });