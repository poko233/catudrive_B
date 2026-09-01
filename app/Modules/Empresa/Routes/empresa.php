<?php

declare(strict_types=1);

use App\Modules\Empresa\Controllers\EmpresaController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])
    ->prefix('empresa')
    ->controller(EmpresaController::class)
    ->group(function (): void {

        Route::get('/mi-empresa', 'miEmpresa')
            ->middleware('permiso:Configuracion,Empresa,Ver')
            ->name('empresa.mine');

        Route::get('/', 'index')
            ->middleware('permiso:Configuracion,Empresa,Ver')
            ->name('empresa.index');

        Route::get('/{empresa}', 'show')
            ->whereNumber('empresa')
            ->middleware('permiso:Configuracion,Empresa,Ver')
            ->name('empresa.show');

        Route::post('/', 'store')
            ->middleware([
                'permiso:Configuracion,Empresa,Crear',
                'throttle:write',
            ])
            ->name('empresa.store');

        Route::put('/{empresa}', 'update')
            ->whereNumber('empresa')
            ->middleware([
                'permiso:Configuracion,Empresa,Editar',
                'throttle:write',
            ])
            ->name('empresa.update');

        Route::patch('/{empresa}', 'update')
            ->whereNumber('empresa')
            ->middleware([
                'permiso:Configuracion,Empresa,Editar',
                'throttle:write',
            ]);

        Route::delete('/{empresa}', 'destroy')
            ->whereNumber('empresa')
            ->middleware([
                'permiso:Configuracion,Empresa,Eliminar',
                'throttle:delete',
            ])
            ->name('empresa.destroy');

        Route::post('/{empresa}/imagen/{tipo}', 'uploadImagen')
            ->whereNumber('empresa')
            ->where('tipo', 'logo_cuadrado|logo_largo|icono|banner')
            ->middleware([
                'permiso:Configuracion,Empresa,Editar',
                'throttle:uploads',
            ])
            ->name('empresa.image.upload');

        Route::delete('/{empresa}/imagen/{tipo}', 'deleteImagen')
            ->whereNumber('empresa')
            ->where('tipo', 'logo_cuadrado|logo_largo|icono|banner')
            ->middleware([
                'permiso:Configuracion,Empresa,Editar',
                'throttle:delete',
            ])
            ->name('empresa.image.delete');
    });
