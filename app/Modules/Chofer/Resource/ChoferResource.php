<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Resource;

use App\Shared\Traits\GeneraUrlArchivo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChoferResource extends JsonResource
{
    use GeneraUrlArchivo;

    public function toArray(
        Request $request
    ): array {
        $usuario =
            $this->usuario;

        return [
            'id' =>
                (int)
                $this->id,

            'carnet_sindical' =>
                $this->carnet_sindical,

            'fotografia' =>
                $usuario?->foto,

            'fotoUrl' =>
                $this->urlArchivo(
                    $usuario?->foto
                ),

            'nombre_completo' =>
                $this->nombreCompleto(),

            'carnet_identidad' =>
                $usuario?->ci,

            'telefono' =>
                $usuario?->celular
                ?: $usuario?->telefono,

            'numero_licencia' =>
                $this->numero_licencia,

            'categoria_licencia' =>
                $this->categoria_licencia,

            'estado' =>
                mb_strtoupper(
                    trim(
                        (string)
                        $usuario?->estado
                    )
                ),

            'codigo_qr' =>
                $usuario?->codigo_qr,

            'qrUrl' =>
                $this->qrUrl(
                    $usuario?->codigo_qr
                ),

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }

    private function nombreCompleto(): string
    {
        $usuario =
            $this->usuario;

        if (!$usuario) {
            return '';
        }

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                implode(
                    ' ',
                    array_filter([
                        $usuario->nombres,

                        $usuario->primer_apellido !== '-'
                            ? $usuario->primer_apellido
                            : '',

                        $usuario->segundo_apellido,
                    ])
                )
            ) ?? ''
        );
    }

    private function qrUrl(
        ?string $qr
    ): ?string {
        $qr = trim(
            (string)
            $qr
        );

        if ($qr === '') {
            return null;
        }

        if (
            str_starts_with(
                $qr,
                'data:image/'
            ) ||
            str_starts_with(
                $qr,
                'http://'
            ) ||
            str_starts_with(
                $qr,
                'https://'
            )
        ) {
            return $this->urlArchivo(
                $qr
            );
        }

        return
            'data:image/png;base64,' .
            $qr;
    }
}