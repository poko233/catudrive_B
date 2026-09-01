<?php

declare(strict_types=1);

namespace App\Modules\Formulario\Resource;

use App\Modules\Modulo\Resource\ModuloResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormularioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                (int) $this->id,

            'formulario' =>
                $this->formulario,

            'descripcion' =>
                $this->descripcion,

            'ruta' =>
                $this->ruta,

            'estado' =>
                $this->estado,

            'modulos' =>
                ModuloResource::collection(
                    $this->whenLoaded(
                        'modulos'
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
