<?php

declare(strict_types=1);

use App\Modules\Empresa\Controllers\EmpresaController;
use App\Modules\Sucursal\Controllers\SucursalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sucursales disponibles para el usuario
|--------------------------------------------------------------------------
|
| Esta ruta debe poder llamarse ANTES de que el frontend seleccione
| X-Sucursal-Id. Por eso no utiliza el middleware "sucursal".
|
*/

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])
    ->get(
        '/sucursales/mis-sucursales',
        [SucursalController::class, 'misSucursales']
    )
    ->name('sucursales.mine');

/*
|--------------------------------------------------------------------------
| CRUD de Sucursales
|--------------------------------------------------------------------------
|
| Sucursales es configuración administrativa global. No aplicamos aquí
| CheckSucursal porque un administrador debe poder administrar otras
| sucursales sin quedar limitado a la sucursal actualmente seleccionada.
|
*/

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])
    ->prefix('sucursales')
    ->controller(SucursalController::class)
    ->group(function (): void {

        Route::get(
            '/',
            'index'
        )
            ->middleware(
                'permiso:Configuracion,Sucursales,Ver'
            )
            ->name('sucursales.index');

        Route::get(
            '/{sucursal}',
            'show'
        )
            ->whereNumber('sucursal')
            ->middleware(
                'permiso:Configuracion,Sucursales,Ver'
            )
            ->name('sucursales.show');

        Route::post(
            '/',
            'store'
        )
            ->middleware([
                'permiso:Configuracion,Sucursales,Crear',
                'throttle:write',
            ])
            ->name('sucursales.store');

        Route::put(
            '/{sucursal}',
            'update'
        )
            ->whereNumber('sucursal')
            ->middleware([
                'permiso:Configuracion,Sucursales,Editar',
                'throttle:write',
            ])
            ->name('sucursales.update');

        Route::patch(
            '/{sucursal}',
            'update'
        )
            ->whereNumber('sucursal')
            ->middleware([
                'permiso:Configuracion,Sucursales,Editar',
                'throttle:write',
            ]);

        Route::delete(
            '/{sucursal}',
            'destroy'
        )
            ->whereNumber('sucursal')
            ->middleware([
                'permiso:Configuracion,Sucursales,Eliminar',
                'throttle:delete',
            ])
            ->name('sucursales.destroy');
    });

/*
|--------------------------------------------------------------------------
| Compatibilidad: empresas y sucursales por empresa
|--------------------------------------------------------------------------
|
| Se conserva la ruta /empresas utilizada por la vista de sucursales.
|
*/

Route::middleware([
    'auth:sanctum',
    'usuario.activo',
    'throttle:api',
])
    ->prefix('empresas')
    ->group(function (): void {

        Route::get(
            '/',
            [EmpresaController::class, 'index']
        )
            ->middleware(
                'permiso:Configuracion,Empresa,Ver'
            )
            ->name('sucursales.empresas.index');

        Route::get(
            '/{idEmpresa}/sucursales',
            [SucursalController::class, 'porEmpresa']
        )
            ->whereNumber('idEmpresa')
            ->middleware(
                'permiso:Configuracion,Sucursales,Ver'
            )
            ->name('sucursales.by-company');
    });
