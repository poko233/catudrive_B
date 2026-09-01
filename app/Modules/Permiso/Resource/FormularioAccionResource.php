<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormularioAccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                (int) $this->id,

            'id_rol' =>
                (int) $this->id_rol,

            'rol' =>
                $this->whenLoaded(
                    'rol',
                    fn () =>
                        $this->rol?->rol
                ),

            'id_formulario' =>
                (int) $this->id_formulario,

            'formulario' =>
                $this->whenLoaded(
                    'formulario',
                    fn () =>
                        $this->formulario?->formulario
                ),

            'selector_html' =>
                $this->selector_html,

            'habilitado' =>
                (bool) $this->habilitado,

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }
}
