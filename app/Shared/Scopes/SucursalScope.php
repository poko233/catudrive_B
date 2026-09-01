<?php

declare(strict_types=1);

namespace App\Shared\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SucursalScope implements Scope
{
    public function apply(
        Builder $builder,
        Model $model
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Procesos de consola
        |--------------------------------------------------------------------------
        |
        | Migrations, seeders, comandos Artisan, jobs ejecutados en
        | consola, etc. no dependen de una sucursal HTTP.
        |
        */

        if (app()->runningInConsole()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener sucursal validada
        |--------------------------------------------------------------------------
        |
        | No confiamos directamente en:
        |
        | X-Sucursal-Id
        |
        | El middleware CheckSucursal debe validar primero:
        |
        | - que exista;
        | - que sea numérico;
        | - que el usuario pertenezca a esa sucursal.
        |
        | Después almacena:
        |
        | request()->attributes->set('id_sucursal', ...)
        |
        */

        $idSucursal =
            request()
                ->attributes
                ->get(
                    'id_sucursal'
                );

        if (
            is_numeric(
                $idSucursal
            ) &&
            (int) $idSucursal > 0
        ) {
            $builder->where(
                $model->getTable()
                    . '.id_sucursal',

                (int) $idSucursal
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Fail closed
        |--------------------------------------------------------------------------
        |
        | Si existe un usuario autenticado pero la petición llegó hasta
        | un modelo protegido por sucursal sin haber pasado correctamente
        | por CheckSucursal, NO devolvemos registros.
        |
        | Esto evita un posible problema como:
        |
        | olvidar middleware('sucursal')
        |          ↓
        | consultar tabla
        |          ↓
        | devolver registros de todas las sucursales
        |
        */

        if (Auth::check()) {
            $builder->whereRaw(
                '1 = 0'
            );
        }
    }
}