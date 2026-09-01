<?php

declare(strict_types=1);

namespace App\Modules\Modulo\Resource;

use App\Modules\Formulario\Resource\FormularioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuloResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                (int) $this->id,

            'modulo' =>
                $this->modulo,

            'descripcion' =>
                $this->descripcion,

            'icono' =>
                $this->icono,

            'orden' =>
                (int) $this->orden,

            'estado' =>
                $this->estado,

            'formularios' =>
                FormularioResource::collection(
                    $this->whenLoaded(
                        'formularios'
                    )
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