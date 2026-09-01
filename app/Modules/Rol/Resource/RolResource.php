<?php

declare(strict_types=1);

namespace App\Modules\Rol\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' =>
                (int) $this->id,

            'rol' =>
                $this->rol,

            'descripcion' =>
                $this->descripcion,

            'estado' =>
                $this->estado,

            'permisos' =>
                $this->when(
                    $this->relationLoaded(
                        'permisos'
                    ),
                    fn () =>
                        $this->permisos
                            ->groupBy(
                                'id_modulo'
                            )
                            ->map(
                                function (
                                    $grupoModulo
                                ): array {
                                    $primero =
                                        $grupoModulo
                                            ->first();

                                    return [
                                        'modulo' =>
                                            $primero
                                                ->modulo
                                                ?->modulo,

                                        'id_modulo' =>
                                            (int)
                                            $primero
                                                ->id_modulo,

                                        'formularios' =>
                                            $grupoModulo
                                                ->groupBy(
                                                    'id_formulario'
                                                )
                                                ->map(
                                                    function (
                                                        $grupoForm
                                                    ): array {
                                                        $permiso =
                                                            $grupoForm
                                                                ->first();

                                                        return [
                                                            'formulario' =>
                                                                $permiso
                                                                    ->formulario
                                                                    ?->formulario,

                                                            'id_formulario' =>
                                                                (int)
                                                                $permiso
                                                                    ->id_formulario,

                                                            'acciones' =>
                                                                $grupoForm
                                                                    ->map(
                                                                        static fn ($p) => [
                                                                            'id_accion' =>
                                                                                (int)
                                                                                $p->id_accion,

                                                                            'accion' =>
                                                                                $p
                                                                                    ->accion
                                                                                    ?->accion,
                                                                        ]
                                                                    )
                                                                    ->values(),
                                                        ];
                                                    }
                                                )
                                                ->values(),
                                    ];
                                }
                            )
                            ->values()
                ),

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }
}
