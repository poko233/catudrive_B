<?php

declare(strict_types=1);

namespace App\Modules\Empresa\Resources;

use App\Shared\Traits\GeneraUrlArchivo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpresaResource extends JsonResource
{
    use GeneraUrlArchivo;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,

            'empresa' => $this->empresa,
            'slogan' => $this->slogan,
            'sigla' => $this->sigla,

            'telefono' => $this->telefono,
            'celular' => $this->celular,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'responsable' => $this->responsable,

            'latitud' => $this->latitud,
            'longitud' => $this->longitud,

            'objeto' => $this->objeto,
            'mision' => $this->mision,
            'vision' => $this->vision,

            'estado' => $this->estado,

            'redes' => [
                'facebook' => $this->facebook,
                'instagram' => $this->instagram,
                'tiktok' => $this->tiktok,
                'linkedin' => $this->linkedin,
            ],

            'carrito' => $this->carrito,
            'tipo_cambio' => $this->tipo_cambio,

            'logos' => [
                'cuadrado' => $this->urlArchivo($this->logo_cuadrado),
                'largo' => $this->urlArchivo($this->logo_largo),
                'baner' => $this->urlArchivo($this->baner_inicio),
                'icono' => $this->urlArchivo($this->icono),
            ],

            'cierre' => [
                'titulo' => $this->titulo_cierre,
                'mensaje' => $this->mensaje_cierre,
            ],

            'inicio' => [
                'titulo' => $this->titulo_inicio,
                'mensaje' => $this->mensaje_inicio,
            ],

            'dominio' => $this->dominio,
            'smtp_correo' => $this->smtp_correo,
            'correo_institucional' => $this->correo_institucional,

            // pwd_institucional nunca se devuelve.
        ];
    }
}
