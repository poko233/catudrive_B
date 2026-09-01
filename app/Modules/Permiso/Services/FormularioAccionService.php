<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Services;

use App\Modules\Auth\Services\SidebarCacheService;
use App\Shared\Models\FormularioAccion;
use App\Shared\Services\AppCacheService;
use App\Shared\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormularioAccionService
{
    public function __construct(
        private readonly SidebarCacheService $sidebarCache,
        private readonly AuditService $audit,
        private readonly AppCacheService $cache
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listar reglas Visibility
    |--------------------------------------------------------------------------
    |
    | Este endpoint es un excelente candidato para caché porque:
    |
    | - no utiliza filtros;
    | - no utiliza paginación;
    | - devuelve el mismo catálogo administrativo;
    | - cambia solamente cuando se crea/edita/elimina una regla.
    |
    */

    public function list(): Collection
    {
        /** @var Collection<int, FormularioAccion> $reglas */
        $reglas =
            $this->cache->remember(
                AppCacheService::FORMULARIO_ACCIONES,
                static fn (): Collection =>
                    FormularioAccion::query()
                        ->with([
                            'rol:id,rol',
                            'formulario:id,formulario',
                        ])
                        ->orderBy(
                            'id_rol'
                        )
                        ->orderBy(
                            'id_formulario'
                        )
                        ->orderBy(
                            'selector_html'
                        )
                        ->get()
            );

        return $reglas;
    }

    /*
    |--------------------------------------------------------------------------
    | Crear regla Visibility
    |--------------------------------------------------------------------------
    */

    public function store(
        array $data
    ): FormularioAccion {
        /*
        |--------------------------------------------------------------------------
        | Verificar acceso RBAC
        |--------------------------------------------------------------------------
        */

        $this->ensureRoleHasFormularioAccess(
            (int) $data['id_rol'],
            (int) $data['id_formulario']
        );

        /*
        |--------------------------------------------------------------------------
        | Estado por defecto
        |--------------------------------------------------------------------------
        */

        $data['habilitado'] =
            (bool) (
                $data['habilitado']
                ?? false
            );

        /*
        |--------------------------------------------------------------------------
        | Crear
        |--------------------------------------------------------------------------
        */

        $regla =
            FormularioAccion::query()
                ->create(
                    $data
                );

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheRol(
            (int) $regla->id_rol
        );

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        $this->audit->created(
            resource:
                'FormularioAccion',

            resourceId:
                $regla->id,

            after:
                $regla->toArray(),
        );

        return $regla->load([
            'rol:id,rol',
            'formulario:id,formulario',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar regla Visibility
    |--------------------------------------------------------------------------
    */

    public function update(
        FormularioAccion $regla,
        array $data
    ): FormularioAccion {
        $before =
            $regla->toArray();

        $idRolAnterior =
            (int) $regla->id_rol;

        $idRolNuevo =
            (int) (
                $data['id_rol']
                ?? $regla->id_rol
            );

        $idFormularioNuevo =
            (int) (
                $data['id_formulario']
                ?? $regla->id_formulario
            );

        /*
        |--------------------------------------------------------------------------
        | Validar acceso RBAC
        |--------------------------------------------------------------------------
        */

        $this->ensureRoleHasFormularioAccess(
            $idRolNuevo,
            $idFormularioNuevo
        );

        /*
        |--------------------------------------------------------------------------
        | Actualizar
        |--------------------------------------------------------------------------
        */

        $regla->fill(
            $data
        );

        $regla->save();

        /*
        |--------------------------------------------------------------------------
        | Invalidar rol anterior
        |--------------------------------------------------------------------------
        */

     $this->invalidarCacheRol(
    $idRolAnterior
);


        /*
        |--------------------------------------------------------------------------
        | Invalidar nuevo rol si cambió
        |--------------------------------------------------------------------------
        */

     if (
    $idRolNuevo !==
    $idRolAnterior
) {
    $this->invalidarCacheRol(
        $idRolNuevo
    );
}

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        $actualizada =
            $regla->fresh();

        $this->audit->updated(
            resource:
                'FormularioAccion',

            resourceId:
                $regla->id,

            before:
                $before,

            after:
                $actualizada->toArray(),
        );

        return $actualizada->load([
            'rol:id,rol',
            'formulario:id,formulario',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar regla Visibility
    |--------------------------------------------------------------------------
    */

    public function delete(
        FormularioAccion $regla
    ): void {
        $before =
            $regla->toArray();

        $idRol =
            (int) $regla->id_rol;

        $id =
            (int) $regla->id;

        /*
        |--------------------------------------------------------------------------
        | Eliminar
        |--------------------------------------------------------------------------
        */

        $regla->delete();

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheRol(
            $idRol
        );

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        $this->audit->deleted(
            resource:
                'FormularioAccion',

            resourceId:
                $id,

            before:
                $before,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validar acceso RBAC
    |--------------------------------------------------------------------------
    */

    private function ensureRoleHasFormularioAccess(
        int $idRol,
        int $idFormulario
    ): void {
        $exists =
            DB::table(
                'formulario_permiso'
            )
                ->where(
                    'id_rol',
                    $idRol
                )
                ->where(
                    'id_formulario',
                    $idFormulario
                )
                ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'id_formulario' => [
                    'El rol no posee permisos sobre este formulario.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidación centralizada
    |--------------------------------------------------------------------------
    */

  private function invalidarCacheRol(
    int $idRol
): void {
    /*
     * SidebarCacheService elimina:
     *
     * - sidebar del rol;
     * - Visibility del rol;
     * - permisos cacheados de los usuarios del rol.
     */

    $this->sidebarCache
        ->forgetRol(
            $idRol
        );

    /*
     * Invalidar el listado administrativo:
     *
     * GET /api/formulario_acciones
     */

    $this->cache
        ->forgetFormularioAcciones();
}
}