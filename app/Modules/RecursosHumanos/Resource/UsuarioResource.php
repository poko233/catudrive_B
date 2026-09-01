<?php

declare(strict_types=1);

namespace App\Modules\RecursosHumanos\Resource;

use App\Shared\Traits\GeneraUrlArchivo;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class UsuarioResource extends JsonResource
{
    use GeneraUrlArchivo;

    public function toArray(
        Request $request
    ): array {
        $roles =
            $this->rolesCargados();

        $esEstudiante =
            $roles->contains(
                static function ($rol): bool {
                    return mb_strtolower(
                        trim(
                            (string)
                            ($rol->rol ?? '')
                        )
                    ) === 'estudiante';
                }
            );

        return [
            'id' =>
                (int) $this->id,

            'usuario' =>
                $this->usuario,

            'ci' =>
                $this->ci,

            'nombres' =>
                $this->nombres,

            /*
            |--------------------------------------------------------------------------
            | Alias frontend
            |--------------------------------------------------------------------------
            */

            'apellidoPaterno' =>
                $this->primer_apellido,

            'apellidoMaterno' =>
                $this->segundo_apellido,

            /*
            |--------------------------------------------------------------------------
            | Nombres reales PostgreSQL
            |--------------------------------------------------------------------------
            */

            'primer_apellido' =>
                $this->primer_apellido,

            'segundo_apellido' =>
                $this->segundo_apellido,

            'genero' =>
                $this->generoParaFrontend(
                    $this->genero
                ),

            'fecha_nac' =>
                $this->fechaNacimiento(),

            'email' =>
                $this->email,

            'telefono' =>
                $this->telefono,

            'celular' =>
                $this->celular,

            'direccion' =>
                $this->direccion,

            'expedido' =>
                $this->expedido,

            /*
            |--------------------------------------------------------------------------
            | QR
            |--------------------------------------------------------------------------
            */

            'codigo_qr' =>
                $this->codigo_qr,

            'qrUrl' =>
                $this->qrUrl(
                    $this->codigo_qr
                ),

            /*
            |--------------------------------------------------------------------------
            | Fotografía
            |--------------------------------------------------------------------------
            */

            'foto' =>
                $this->foto,

            'fotoUrl' =>
                $this->urlArchivo(
                    $this->foto
                ),

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            'estado' =>
                mb_strtoupper(
                    trim(
                        (string)
                        $this->estado
                    )
                ),

            'roles' =>
                $roles
                    ->map(
                        static fn ($rol) => [
                            'idRol' =>
                                (int) $rol->id,

                            'id' =>
                                (int) $rol->id,

                            'rol' =>
                                $rol->rol,
                        ]
                    )
                    ->values(),

            /*
            |--------------------------------------------------------------------------
            | Compatibilidad con frontend actual
            |--------------------------------------------------------------------------
            |
            | Estas tablas todavía no existen en PostgreSQL.
            |
            */

            'matricula' =>
                null,

            'numeroReferencias' =>
                null,

            'numero_referencias' =>
                null,

            'documentos' =>
                [],

            'esEstudiante' =>
                $esEstudiante,

            'created_at' =>
                $this->created_at
                    ?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at
                    ?->toDateTimeString(),
        ];
    }

    private function rolesCargados(): Collection
    {
        if (
            !$this->resource
                ->relationLoaded(
                    'roles'
                )
        ) {
            return collect();
        }

        return collect(
            $this->roles
        );
    }

    private function generoParaFrontend(
        ?string $genero
    ): ?string {
        if (!$genero) {
            return null;
        }

        return match (
            mb_strtolower(
                trim(
                    $genero
                )
            )
        ) {
            'masculino' =>
                'MASCULINO',

            'femenino' =>
                'FEMENINO',

            'otro' =>
                'OTRO',

            default =>
                mb_strtoupper(
                    trim(
                        $genero
                    )
                ),
        };
    }

    private function fechaNacimiento(): ?string
    {
        $fecha =
            $this->fecha_nac;

        if (!$fecha) {
            return null;
        }

        if (
            $fecha instanceof
            DateTimeInterface
        ) {
            return $fecha->format(
                'Y-m-d'
            );
        }

        return substr(
            (string) $fecha,
            0,
            10
        );
    }

    private function qrUrl(
        ?string $qr
    ): ?string {
        if (!$qr) {
            return null;
        }

        $qr =
            trim(
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

        /*
         * Si parece una ruta de archivo, usar el generador
         * seguro de URL.
         */
        if (
            str_contains(
                $qr,
                '/'
            ) ||
            preg_match(
                '/\.(png|jpe?g|webp|svg)$/i',
                $qr
            ) === 1
        ) {
            return $this->urlArchivo(
                $qr
            );
        }

        /*
         * Compatibilidad con QR almacenado directamente
         * como base64 sin prefijo data URI.
         */
        if (
            base64_decode(
                $qr,
                true
            ) !== false
        ) {
            return
                'data:image/png;base64,'
                . $qr;
        }

        return null;
    }
}
