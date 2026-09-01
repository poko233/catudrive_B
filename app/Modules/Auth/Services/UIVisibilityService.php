<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Shared\Services\AppCacheService;
use Illuminate\Support\Facades\DB;

class UIVisibilityService
{
    public function __construct(
        private readonly AppCacheService $cache
    ) {
    }

    /**
     * Devuelve:
     *
     * [
     *     id_formulario => [
     *         '.selector-a',
     *         '#selector-b',
     *     ]
     * ]
     *
     * Solamente se incluyen reglas donde habilitado = false.
     */
    public function getVisibilityForRole(
        int $idRol
    ): array {
        /** @var array $visibility */
        $visibility =
            $this->cache
                ->rememberForRole(
                    $idRol,
                    'visibility',
                    function () use (
                        $idRol
                    ): array {
                        $rows =
                            DB::table(
                                'formulario_accion'
                            )
                                ->where(
                                    'id_rol',
                                    $idRol
                                )
                                ->where(
                                    'habilitado',
                                    false
                                )
                                ->whereNotNull(
                                    'selector_html'
                                )
                                ->select(
                                    'id_formulario',
                                    'selector_html'
                                )
                                ->distinct()
                                ->get();

                        $map = [];

                        foreach (
                            $rows
                            as $row
                        ) {
                            $selector =
                                trim(
                                    (string)
                                    $row->selector_html
                                );

                            if (
                                $selector === ''
                            ) {
                                continue;
                            }

                            $idFormulario =
                                (int)
                                $row->id_formulario;

                            $map[
                                $idFormulario
                            ] ??= [];

                            if (
                                !in_array(
                                    $selector,
                                    $map[
                                        $idFormulario
                                    ],
                                    true
                                )
                            ) {
                                $map[
                                    $idFormulario
                                ][] =
                                    $selector;
                            }
                        }

                        return $map;
                    }
                );

        return $visibility;
    }

    /**
     * Invalida Visibility de un rol.
     */
    public function forgetForRole(
        int $idRol
    ): void {
        $this->cache
            ->forgetRoleVisibility(
                $idRol
            );
    }
}