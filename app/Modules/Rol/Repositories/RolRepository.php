<?php

declare(strict_types=1);

namespace App\Modules\Rol\Repositories;

use App\Shared\Models\Rol;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RolRepository
{
    public function paginar(
        array $filtros
    ): LengthAwarePaginator {
        $query = Rol::query();

        $estado =
            isset($filtros['estado'])
                ? trim(
                    (string) $filtros['estado']
                )
                : null;

        $buscar =
            isset($filtros['buscar'])
                ? trim(
                    (string) $filtros['buscar']
                )
                : null;

        if ($estado) {
            $query->where(
                'estado',
                $estado
            );
        }

        if ($buscar) {
            $query->where(
                function ($subquery) use ($buscar): void {
                    $subquery
                        ->where(
                            'rol',
                            'ilike',
                            '%' . $buscar . '%'
                        )
                        ->orWhere(
                            'descripcion',
                            'ilike',
                            '%' . $buscar . '%'
                        );
                }
            );
        }

        $porPagina =
            (int) (
                $filtros['por_pagina']
                ?? 15
            );

        $porPagina = max(
            1,
            min(
                $porPagina,
                100
            )
        );

        return $query
            ->orderBy('rol')
            ->paginate(
                $porPagina
            );
    }

    public function conPermisos(
        Rol $rol
    ): Rol {
        return $rol->load([
            'permisos' =>
                fn ($query) =>
                    $query
                        ->whereHas(
                            'modulo',
                            fn ($q) =>
                                $q->where(
                                    'estado',
                                    'Activo'
                                )
                        )
                        ->whereHas(
                            'formulario',
                            fn ($q) =>
                                $q->where(
                                    'estado',
                                    'Activo'
                                )
                        )
                        ->select([
                            'id',
                            'id_rol',
                            'id_modulo',
                            'id_formulario',
                            'id_accion',
                            'created_at',
                            'updated_at',
                        ]),

            'permisos.modulo:id,modulo,icono,estado',

            'permisos.formulario:id,formulario,ruta,estado',

            'permisos.accion:id,accion',
        ]);
    }

    public function crear(
        array $datos
    ): Rol {
        return Rol::query()
            ->create(
                $datos
            );
    }

    public function actualizar(
        Rol $rol,
        array $datos
    ): Rol {
        $rol->fill(
            $datos
        );

        $rol->save();

        return $rol->fresh();
    }

    public function eliminar(
        Rol $rol
    ): void {
        $rol->delete();
    }

    public function todosConPermisos(): Collection
    {
        return Rol::query()
            ->with([
                'permisos' =>
                    fn ($query) =>
                        $query
                            ->whereHas(
                                'modulo',
                                fn ($q) =>
                                    $q->where(
                                        'estado',
                                        'Activo'
                                    )
                            )
                            ->whereHas(
                                'formulario',
                                fn ($q) =>
                                    $q->where(
                                        'estado',
                                        'Activo'
                                    )
                            )
                            ->select([
                                'id',
                                'id_rol',
                                'id_modulo',
                                'id_formulario',
                                'id_accion',
                                'created_at',
                                'updated_at',
                            ]),

                'permisos.modulo:id,modulo,icono,estado',

                'permisos.formulario:id,formulario,ruta,estado',

                'permisos.accion:id,accion',
            ])
            ->orderBy('rol')
            ->get();
    }
}