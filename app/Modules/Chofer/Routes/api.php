<?php

declare(strict_types=1);

use App\Modules\Chofer\Controllers\ChoferController;
use App\Modules\Chofer\Controllers\ChoferDashboardController;
use App\Modules\Chofer\Controllers\ChoferReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DASHBOARD DE CHOFERES
|--------------------------------------------------------------------------
|
| El controlador verifica los roles en el servidor:
| - Administrador / Superadmin: todos los choferes.
| - Chofer: exclusivamente su registro.
| - Otros roles: 403.
|
| No utiliza el permiso CRUD Choferes,Ver, porque el chofer necesita
| ver sus propios datos sin obtener acceso al listado administrativo.
|
*/

Route::get(
    '/dashboard/choferes',
    [ChoferDashboardController::class, 'index']
)
    ->middleware([
        'auth:sanctum',
        'usuario.activo',
        'throttle:api',
    ])
    ->name('dashboard.choferes');

/*
|--------------------------------------------------------------------------
| CRUD CHOFERES
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| REPORTES DE CHOFERES
|--------------------------------------------------------------------------
|
| Mismo patrón de Vehiculo/Routes/api.php:
|
| /api/choferes/reportes/{tipo}/html
| /api/choferes/reportes/{tipo}/pdf
|
*/

Route::prefix(
    'choferes/reportes'
)
    ->middleware([
        'auth:sanctum',
        'usuario.activo',
    ])
    ->controller(
        ChoferReporteController::class
    )
    ->group(function (): void {
        Route::get(
            '/{tipo}/html',
            'html'
        )
            ->whereIn(
                'tipo',
                [
                    'lista',
                    'activos_inactivos',
                    'carnets_sindicales',
                ]
            )
            ->middleware(
                'throttle:api',
                'permiso:Choferes,Reportes Chof.,Ver'
            )
            ->name(
                'choferes.reportes.html'
            );

        Route::get(
            '/{tipo}/pdf',
            'pdf'
        )
            ->whereIn(
                'tipo',
                [
                    'lista',
                    'activos_inactivos',
                    'carnets_sindicales',
                ]
            )
            ->middleware(
                'throttle:api',
                'permiso:Choferes,Reportes Chof.,Ver'
            )
            ->name(
                'choferes.reportes.pdf'
            );
    });
