<?php

declare(strict_types=1);

namespace App\Shared\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EmpresaScope implements Scope
{
    /**
     * Filtrado automático por empresa DESACTIVADO.
     *
     * Actualmente la estructura real del sistema no utiliza
     * id_empresa de forma consistente en todos los módulos.
     *
     * Conservamos el Scope para poder reactivar multiempresa
     * en el futuro sin tener que modificar los modelos que ya
     * utilicen BelongsToEmpresa.
     */
    public function apply(
        Builder $builder,
        Model $model
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Multiempresa desactivado
        |--------------------------------------------------------------------------
        |
        | No aplicar filtros por ahora.
        |
        */

        return;
    }
}