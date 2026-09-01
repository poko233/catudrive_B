<?php

declare(strict_types=1);

namespace App\Modules\Sucursal\Resources;

use App\Shared\Traits\GeneraUrlArchivo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SucursalResource extends JsonResource
{
    use GeneraUrlArchivo;

    public function toArray(
        Request $request
    ): array {
        $imagenUrl =
            $this->imagen
                ? $this->urlArchivo(
                    'storage/' .
                    ltrim(
                        (string)
                        $this->imagen,
                        '/'
                    )
                )
                : null;

        return [
            'id' =>
                (int) $this->id,

            'id_empresa' =>
                (int) $this->id_empresa,

            'empresa' =>
                $this->whenLoaded(
                    'empresa',
                    fn () => [
                        'id' =>
                            (int)
                            $this->empresa->id,

                        'empresa' =>
                            $this->empresa->empresa,
                    ]
                ),

            'sucursal' =>
                $this->sucursal,

            'responsable' =>
                $this->responsable,

            'direccion' =>
                $this->direccion,

            'longitud' =>
                $this->longitud,

            'latitud' =>
                $this->latitud,

            'telefono' =>
                $this->telefono,

            'celular' =>
                $this->celular,

            'email' =>
                $this->email,

            'pais' =>
                $this->pais,

            'ciudad' =>
                $this->ciudad,

            'localidad' =>
                $this->localidad,

            /*
             * Conservamos "imagen" como URL porque así funcionaba
             * el Resource anterior.
             */
            'imagen' =>
                $imagenUrl,

            /*
             * Ruta original para operaciones administrativas.
             */
            'imagenRuta' =>
                $this->imagen,

            'imagenUrl' =>
                $imagenUrl,

            'estado' =>
                $this->estado,

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}
