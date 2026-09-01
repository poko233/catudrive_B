<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Shared\Scopes\SucursalScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use LogicException;

trait BelongsToSucursal
{
    protected static function bootBelongsToSucursal(): void
    {
        static::addGlobalScope(
            new SucursalScope()
        );
    }

    public static function sinFiltroSucursal(): Builder
    {
        return static::withoutGlobalScope(
            SucursalScope::class
        );
    }

    public static function deSucursal(
        int $idSucursal
    ): Builder {
        if ($idSucursal <= 0) {
            throw new LogicException(
                'El identificador de sucursal debe ser mayor a cero.'
            );
        }

        $model =
            new static();

        $table =
            $model->getTable();

        if (
            !Schema::hasColumn(
                $table,
                'id_sucursal'
            )
        ) {
            throw new LogicException(
                sprintf(
                    'El modelo %s utiliza BelongsToSucursal, pero la tabla "%s" no contiene la columna id_sucursal.',
                    static::class,
                    $table,
                )
            );
        }

        return static::withoutGlobalScope(
            SucursalScope::class
        )
            ->where(
                $table . '.id_sucursal',
                $idSucursal
            );
    }
}