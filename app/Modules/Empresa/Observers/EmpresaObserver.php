<?php

declare(strict_types=1);

namespace App\Modules\Empresa\Observers;

use App\Shared\Models\Empresa;

class EmpresaObserver
{
    /**
     * Actualmente no se crean roles automáticamente
     * al crear una empresa.
     *
     * La tabla rol actual no utiliza id_empresa.
     *
     * Conservamos este Observer por compatibilidad
     * en caso de que siga registrado en
     * AppServiceProvider.
     */
    public function created(
        Empresa $empresa
    ): void {
        //
    }
}