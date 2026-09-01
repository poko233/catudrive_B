<?php

declare(strict_types=1);

namespace App\Modules\RecursosHumanos\Services;

use App\Shared\Models\User;
use App\Shared\Services\AuditService;
use App\Shared\Services\ImageOptimizerService;
use App\Shared\Services\TokenSecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class RecursosHumanosService
{
    private const AUDIT_FIELDS = [
        'usuario',
        'ci',
        'nombres',
        'primer_apellido',
        'segundo_apellido',
        'genero',
        'fecha_nac',
        'email',
        'telefono',
        'celular',
        'direccion',
        'expedido',
        'estado',
        'foto',
    ];

    public function __construct(
        private readonly ImageOptimizerService $imageOptimizer,
        private readonly TokenSecurityService $tokenSecurity,
        private readonly AuditService $audit
    ) {
    }

    /**
     * La BD PostgreSQL actual contiene datos generales del usuario,
     * roles y sucursales.
     *
     * No existen actualmente:
     *
     * - Estudiante
     * - NumeroReferencia
     * - DocumentoEstudiante
     */
    public function listarUsuarios(): Collection
    {
        return User::query()
            ->with([
                'roles:id,rol,estado',
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function obtenerUsuario(
        int $id
    ): User {
        return User::query()
            ->with([
                'roles:id,rol,estado',
            ])
            ->findOrFail(
                $id
            );
    }

    public function actualizarUsuario(
        int $id,
        array $data
    ): User {
        $estadoFinal = null;
        $before = [];
        $after = [];

        DB::transaction(
            function () use (
                $id,
                $data,
                &$estadoFinal,
                &$before,
                &$after
            ): void {
                /** @var User $usuario */
                $usuario =
                    User::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                $before =
                    $usuario->only(
                        self::AUDIT_FIELDS
                    );

                /*
                |--------------------------------------------------------------------------
                | Campos generales
                |--------------------------------------------------------------------------
                |
                | El frontend usa apellidoPaterno / apellidoMaterno.
                | PostgreSQL usa primer_apellido / segundo_apellido.
                |
                */

                if (
                    array_key_exists(
                        'usuario',
                        $data
                    )
                ) {
                    $usuario->usuario =
                        $data['usuario'];
                }

                if (
                    array_key_exists(
                        'ci',
                        $data
                    )
                ) {
                    $usuario->ci =
                        $data['ci'];
                }

                if (
                    array_key_exists(
                        'nombres',
                        $data
                    )
                ) {
                    $usuario->nombres =
                        $data['nombres'];
                }

                if (
                    array_key_exists(
                        'apellidoPaterno',
                        $data
                    )
                ) {
                    $usuario->primer_apellido =
                        $data[
                            'apellidoPaterno'
                        ];
                }

                if (
                    array_key_exists(
                        'apellidoMaterno',
                        $data
                    )
                ) {
                    $usuario->segundo_apellido =
                        $data[
                            'apellidoMaterno'
                        ];
                }

                if (
                    array_key_exists(
                        'genero',
                        $data
                    )
                ) {
                    $usuario->genero =
                        $this->generoParaBase(
                            $data['genero']
                        );
                }

                if (
                    array_key_exists(
                        'fecha_nac',
                        $data
                    )
                ) {
                    $usuario->fecha_nac =
                        $data[
                            'fecha_nac'
                        ];
                }

                foreach (
                    [
                        'email',
                        'telefono',
                        'celular',
                        'direccion',
                        'expedido',
                    ] as $campo
                ) {
                    if (
                        array_key_exists(
                            $campo,
                            $data
                        )
                    ) {
                        $usuario->{$campo} =
                            $data[$campo];
                    }
                }

                if (
                    array_key_exists(
                        'estado',
                        $data
                    )
                ) {
                    $usuario->estado =
                        $this->estadoParaBase(
                            $data['estado']
                        );
                }

                $usuario->save();

                $estadoFinal =
                    mb_strtoupper(
                        trim(
                            (string)
                            $usuario->estado
                        )
                    );

                $after =
                    $usuario
                        ->fresh()
                        ->only(
                            self::AUDIT_FIELDS
                        );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Revocar sesiones del usuario inactivo
        |--------------------------------------------------------------------------
        |
        | Se hace después de completar correctamente la transacción.
        |
        */

        if (
            $estadoFinal ===
            'INACTIVO'
        ) {
            $this
                ->tokenSecurity
                ->revokeAllTokensByUserId(
                    $id
                );
        }

        $this->audit->updated(
            resource:
                'UsuarioRRHH',

            resourceId:
                $id,

            before:
                $before,

            after:
                $after,
        );

        return $this->obtenerUsuario(
            $id
        );
    }

    public function actualizarFoto(
        int $id,
        UploadedFile $foto
    ): User {
        /** @var User $usuario */
        $usuario =
            User::query()
                ->findOrFail(
                    $id
                );

        $rutaAnterior =
            is_string(
                $usuario->foto
            )
                ? $usuario->foto
                : null;

        /*
        |--------------------------------------------------------------------------
        | Crear primero la nueva imagen
        |--------------------------------------------------------------------------
        */

        $nombreBase =
            sprintf(
                'u%d_%s',
                $usuario->id,
                Str::lower(
                    Str::random(20)
                )
            );

        $nombreArchivo =
            $this
                ->imageOptimizer
                ->convertToWebP(
                    $foto,
                    public_path(
                        'fotos-usuarios'
                    ),
                    $nombreBase
                );

        $rutaNueva =
            'fotos-usuarios/'
            . $nombreArchivo;

        /*
         * La columna foto actual es varchar(80). El nombre generado
         * queda ampliamente por debajo de ese límite.
         */

        try {
            DB::transaction(
                function () use (
                    $usuario,
                    $rutaNueva
                ): void {
                    $usuario->foto =
                        $rutaNueva;

                    $usuario->save();
                }
            );
        } catch (Throwable $exception) {
            $this->eliminarFotoLocal(
                $rutaNueva
            );

            throw $exception;
        }

        /*
        |--------------------------------------------------------------------------
        | Eliminar fotografía anterior únicamente después del éxito
        |--------------------------------------------------------------------------
        */

        if (
            $rutaAnterior !==
            $rutaNueva
        ) {
            $this->eliminarFotoLocal(
                $rutaAnterior
            );
        }

        $this->audit->updated(
            resource:
                'UsuarioRRHH',

            resourceId:
                (int) $usuario->id,

            before: [
                'foto' =>
                    $rutaAnterior,
            ],

            after: [
                'foto' =>
                    $rutaNueva,
            ],
        );

        return $this->obtenerUsuario(
            $id
        );
    }

    private function generoParaBase(
        ?string $genero
    ): ?string {
        if (
            $genero === null ||
            trim($genero) === ''
        ) {
            return null;
        }

        return match (
            mb_strtoupper(
                trim(
                    $genero
                )
            )
        ) {
            'MASCULINO' =>
                'Masculino',

            'FEMENINO' =>
                'Femenino',

            'OTRO' =>
                'Otro',

            default =>
                null,
        };
    }

    private function estadoParaBase(
        string $estado
    ): string {
        return mb_strtoupper(
            trim(
                $estado
            )
        ) === 'INACTIVO'
            ? 'Inactivo'
            : 'Activo';
    }

    /**
     * Solo elimina fotografías locales dentro de public/fotos-usuarios.
     */
    private function eliminarFotoLocal(
        ?string $ruta
    ): void {
        if (!$ruta) {
            return;
        }

        $ruta =
            str_replace(
                '\\',
                '/',
                trim(
                    $ruta
                )
            );

        if (
            str_starts_with(
                $ruta,
                'http://'
            ) ||
            str_starts_with(
                $ruta,
                'https://'
            ) ||
            str_starts_with(
                $ruta,
                'data:image/'
            )
        ) {
            return;
        }

        $archivo =
            basename(
                $ruta
            );

        if (
            $archivo === '' ||
            $archivo === '.' ||
            $archivo === '..'
        ) {
            return;
        }

        $destino =
            public_path(
                'fotos-usuarios/'
                . $archivo
            );

        if (
            File::isFile(
                $destino
            )
        ) {
            File::delete(
                $destino
            );
        }
    }
}
