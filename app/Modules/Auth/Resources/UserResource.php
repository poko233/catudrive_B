<?php

declare(strict_types=1);

namespace App\Modules\Auth\Resources;

use App\Shared\Traits\GeneraUrlArchivo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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

            'apellidoPaterno' => $this->primer_apellido,
            'apellidoMaterno' => $this->segundo_apellido,

            'email' => $this->email,
            'estado' => $this->estado,

            'foto' => $this->foto,
            'fotoUrl' => $fotoUrl,
            'foto_url' => $fotoUrl,

            'codigo_qr' => $this->codigo_qr,
            'qrUrl' => $qrUrl,
            'qr_url' => $qrUrl,

            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles
                    ->map(fn ($rol) => [
                        'id' => (int) $rol->id,
                        'rol' => $rol->rol,
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
