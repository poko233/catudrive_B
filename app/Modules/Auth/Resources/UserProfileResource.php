<?php

declare(strict_types=1);

namespace App\Modules\Auth\Resources;

use App\Shared\Traits\GeneraUrlArchivo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    use GeneraUrlArchivo;

    public function toArray(Request $request): array
    {
        $fotoUrl = $this->urlArchivo($this->foto);
        $qrUrl = $this->urlArchivo($this->codigo_qr);

        return [
            'id' => (int) $this->id,
            'usuario' => $this->usuario,
            'nombres' => $this->nombres,

            'primer_apellido' => $this->primer_apellido,
            'segundo_apellido' => $this->segundo_apellido,

            // Alias útiles para el frontend actual.
            'apellidoPaterno' => $this->primer_apellido,
            'apellidoMaterno' => $this->segundo_apellido,

            'ci' => $this->ci,
            'expedido' => $this->expedido,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'celular' => $this->celular,
            'direccion' => $this->direccion,
            'genero' => $this->genero,
            'fecha_nac' => $this->fecha_nac,

            'foto' => $this->foto,
            'fotoUrl' => $fotoUrl,
            'foto_url' => $fotoUrl,

            'codigo_qr' => $this->codigo_qr,
            'qrUrl' => $qrUrl,
            'qr_url' => $qrUrl,

            'estado' => $this->estado,

            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles
                    ->map(fn ($rol) => [
                        'id' => (int) $rol->id,
                        'rol' => $rol->rol,
                        'descripcion' => $rol->descripcion,
                        'estado' => $rol->estado,
                    ])
                    ->values()
            ),

            'sucursales' => $this->whenLoaded(
                'sucursales',
                fn () => $this->sucursales
                    ->map(fn ($sucursal) => [
                        'id' => (int) $sucursal->id,
                        'sucursal' => $sucursal->sucursal,
                        'ciudad' => $sucursal->ciudad,
                        'estado' => $sucursal->estado,
                    ])
                    ->values()
            ),
        ];
    }
}
