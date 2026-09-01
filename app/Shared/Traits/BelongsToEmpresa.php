<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Shared\Scopes\EmpresaScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use LogicException;

trait BelongsToEmpresa
{
    protected static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope(
            new EmpresaScope()
        );
    }

    public static function sinFiltroEmpresa(): Builder
    {
        return static::withoutGlobalScope(
            EmpresaScope::class
        );
    }

    public static function deEmpresa(
        int $idEmpresa
    ): Builder {
        if ($idEmpresa <= 0) {
            throw new LogicException(
                'El identificador de empresa debe ser mayor a cero.'
            );
        }

        $model =
            new static();

        $table =
            $model->getTable();

        if (
            !Schema::hasColumn(
                $table,
                'id_empresa'
            )
        ) {
            throw new LogicException(
                sprintf(
                    'El modelo %s utiliza BelongsToEmpresa, pero la tabla "%s" no contiene la columna id_empresa.',
                    static::class,
                    $table,
                )
            );
        }

        return static::withoutGlobalScope(
            EmpresaScope::class
        )
            ->where(
                $table . '.id_empresa',
                $idEmpresa
            );
    }
}