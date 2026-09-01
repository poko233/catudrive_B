<?php

declare(strict_types=1);

namespace App\Modules\Permiso\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermisoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                (int) $this->id,

            'rol' =>
                $this->whenLoaded(
                    'rol',
                    fn () => [
                        'id' =>
                            (int) $this->rol->id,

                        'rol' =>
                            $this->rol->rol,
                    ]
                ),

            'modulo' =>
                $this->whenLoaded(
                    'modulo',
                    fn () => [
                        'id' =>
                            (int) $this->modulo->id,

                        'modulo' =>
                            $this->modulo->modulo,
                    ]
                ),

            'formulario' =>
                $this->whenLoaded(
                    'formulario',
                    fn () => [
                        'id' =>
                            (int) $this->formulario->id,

                        'formulario' =>
                            $this->formulario->formulario,
                    ]
                ),

            'accion' =>
                $this->whenLoaded(
                    'accion',
                    fn () => [
                        'id' =>
                            (int) $this->accion->id,

                        'accion' =>
                            $this->accion->accion,
                    ]
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
