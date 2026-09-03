<?php

declare(strict_types=1);

namespace App\Modules\Chofer\Services;

use App\Shared\Models\Chofer;
use App\Shared\Models\User;
use App\Shared\Services\AuditService;
use App\Shared\Services\ImageOptimizerService;
use App\Shared\Services\QrService;
use App\Shared\Services\TokenSecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ChoferService
{
    public function __construct(
        private readonly QrService $qrService,
        private readonly ImageOptimizerService $imageOptimizer,
        private readonly TokenSecurityService $tokenSecurity,
        private readonly AuditService $audit
    ) {
    }

    public function listar(): Collection
    {
        return Chofer::query()
            ->with('usuario')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function obtener(
        int $id
    ): Chofer {
        return Chofer::query()
            ->with('usuario')
            ->findOrFail(
                $id
            );
    }

    public function crear(
        array $data
    ): Chofer {
        $chofer = DB::transaction(
            function () use (
                $data
            ): Chofer {
                [
                    $nombres,
                    $primerApellido,
                    $segundoApellido,
                ] =
                    $this->partirNombreCompleto(
                        $data[
                            'nombre_completo'
                        ]
                    );

                $ci =
                    (string)
                    $data[
                        'carnet_identidad'
                    ];

                $usuario =
                    User::query()
                        ->create([
                            /*
                             * Usuario interno/técnico.
                             * El chofer no necesita credenciales
                             * para ser registrado.
                             */
                            'usuario' =>
                                $this->generarUsuarioTecnico(
                                    $ci
                                ),

                            'password' =>
                                Hash::make(
                                    Str::random(
                                        32
                                    )
                                ),

                            'ci' =>
                                $ci,

                            'nombres' =>
                                $nombres,

                            'primer_apellido' =>
                                $primerApellido,

                            'segundo_apellido' =>
                                $segundoApellido,

                            'celular' =>
                                $data[
                                    'telefono'
                                ],

                            'estado' =>
                                $this->estadoBase(
                                    $data[
                                        'estado'
                                    ]
                                ),
                        ]);

                $chofer =
                    Chofer::query()
                        ->create([
                            'id' =>
                                (int)
                                $usuario->id,

                            'carnet_sindical' =>
                                $data[
                                    'carnet_sindical'
                                ],

                            'numero_licencia' =>
                                $data[
                                    'numero_licencia'
                                ],

                            'categoria_licencia' =>
                                $data[
                                    'categoria_licencia'
                                ],
                        ]);

                /*
                 * QR usando EXACTAMENTE el mismo user.id.
                 */
                $usuario->codigo_qr =
                    $this->qrService
                        ->generateQrImage(
                            (int)
                            $usuario->id
                        );

                $usuario->save();

                return $chofer;
            }
        );

        $fresh =
            $this->obtener(
                (int)
                $chofer->id
            );

        $this->audit->created(
            resource:
                'Chofer',

            resourceId:
                $fresh->id,

            after:
                $this->snapshot(
                    $fresh
                ),
        );

        return $fresh;
    }

    public function actualizar(
        int $id,
        array $data
    ): Chofer {
        $before = [];

        DB::transaction(
            function () use (
                $id,
                $data,
                &$before
            ): void {
                $chofer =
                    Chofer::query()
                        ->with('usuario')
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                $usuario =
                    User::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                $before =
                    $this->snapshot(
                        $chofer
                    );

                [
                    $nombres,
                    $primerApellido,
                    $segundoApellido,
                ] =
                    $this->partirNombreCompleto(
                        $data[
                            'nombre_completo'
                        ]
                    );

                $usuario->update([
                    'ci' =>
                        $data[
                            'carnet_identidad'
                        ],

                    'nombres' =>
                        $nombres,

                    'primer_apellido' =>
                        $primerApellido,

                    'segundo_apellido' =>
                        $segundoApellido,

                    'celular' =>
                        $data[
                            'telefono'
                        ],

                    'estado' =>
                        $this->estadoBase(
                            $data[
                                'estado'
                            ]
                        ),
                ]);

                $chofer->update([
                    'carnet_sindical' =>
                        $data[
                            'carnet_sindical'
                        ],

                    'numero_licencia' =>
                        $data[
                            'numero_licencia'
                        ],

                    'categoria_licencia' =>
                        $data[
                            'categoria_licencia'
                        ],
                ]);
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        if (
            $fresh->usuario?->estado ===
            'Inactivo'
        ) {
            $this->tokenSecurity
                ->revokeAllTokensByUserId(
                    $id
                );
        }

        $this->audit->updated(
            resource:
                'Chofer',

            resourceId:
                $id,

            before:
                $before,

            after:
                $this->snapshot(
                    $fresh
                ),
        );

        return $fresh;
    }

    public function darDeBaja(
        int $id
    ): Chofer {
        $before = [];

        DB::transaction(
            function () use (
                $id,
                &$before
            ): void {
                $chofer =
                    Chofer::query()
                        ->with('usuario')
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                $before =
                    $this->snapshot(
                        $chofer
                    );

                User::query()
                    ->whereKey(
                        $id
                    )
                    ->update([
                        'estado' =>
                            'Inactivo',

                        'updated_at' =>
                            now(),
                    ]);

                /*
                 * Cerrar asignación actual.
                 * El histórico NO se elimina.
                 */
                DB::table(
                    'asignacion_vehiculo_chofer'
                )
                    ->where(
                        'id_chofer',
                        $id
                    )
                    ->where(
                        'estado',
                        'Activo'
                    )
                    ->update([
                        'estado' =>
                            'Inactivo',

                        'updated_at' =>
                            now(),
                    ]);
            }
        );

        $this->tokenSecurity
            ->revokeAllTokensByUserId(
                $id
            );

        $fresh =
            $this->obtener(
                $id
            );

        $this->audit->updated(
            resource:
                'Chofer',

            resourceId:
                $id,

            before:
                $before,

            after:
                $this->snapshot(
                    $fresh
                ),
        );

        return $fresh;
    }

    public function actualizarFoto(
        int $id,
        UploadedFile $foto
    ): Chofer {
        $this->obtener(
            $id
        );

        $usuario =
            User::query()
                ->findOrFail(
                    $id
                );

        $anterior =
            $usuario->foto;

        $nombreBase =
            sprintf(
                'chofer_%d_%s',
                $id,
                Str::lower(
                    Str::random(
                        16
                    )
                )
            );

        /*
         * Reutiliza el optimizador general
         * que ya utiliza CodigoBase/RRHH.
         */
        $nombreArchivo =
            $this->imageOptimizer
                ->convertToWebP(
                    $foto,
                    public_path(
                        'fotos-choferes'
                    ),
                    $nombreBase
                );

        $rutaNueva =
            'fotos-choferes/' .
            $nombreArchivo;

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
        } catch (Throwable $e) {
            $this->eliminarFotoLocal(
                $rutaNueva
            );

            throw $e;
        }

        if (
            $anterior !==
            $rutaNueva
        ) {
            $this->eliminarFotoLocal(
                $anterior
            );
        }

        $this->audit->updated(
            resource:
                'Chofer',

            resourceId:
                $id,

            before: [
                'fotografia' =>
                    $anterior,
            ],

            after: [
                'fotografia' =>
                    $rutaNueva,
            ],
        );

        return $this->obtener(
            $id
        );
    }

    public function regenerarQr(
        int $id
    ): Chofer {
        $this->obtener(
            $id
        );

        $usuario =
            User::query()
                ->findOrFail(
                    $id
                );

        $qr =
            $this->qrService
                ->generateQrImage(
                    $id
                );

        User::withoutEvents(
            function () use (
                $usuario,
                $qr
            ): void {
                $usuario->codigo_qr =
                    $qr;

                $usuario->save();
            }
        );

        $this->audit->updated(
            resource:
                'Chofer',

            resourceId:
                $id,

            before: [
                'codigo_qr' =>
                    '[ANTERIOR]',
            ],

            after: [
                'codigo_qr' =>
                    '[GENERADO]',
            ],
        );

        return $this->obtener(
            $id
        );
    }

    public function historialAsignaciones(
        int $id
    ): array {
        $this->obtener(
            $id
        );

        /*
         * Query Builder no aplica el scope SoftDeletes,
         * por lo que también vemos asignaciones históricas.
         */
        $asignaciones =
            DB::table(
                'asignacion_vehiculo_chofer as avc'
            )
                ->join(
                    'vehiculo as v',
                    'v.id',
                    '=',
                    'avc.id_vehiculo'
                )
                ->where(
                    'avc.id_chofer',
                    $id
                )
                ->select([
                    'avc.id',
                    'avc.id_vehiculo',
                    'avc.estado',
                    'avc.created_at',
                    'avc.updated_at',
                    'avc.deleted_at',

                    'v.placa',
                    'v.tipo',
                    'v.marca',
                    'v.modelo',
                    'v.color',
                    'v.estado as estado_vehiculo',
                ])
                ->orderByDesc(
                    'avc.created_at'
                )
                ->get();

        if (
            $asignaciones
                ->isEmpty()
        ) {
            return [];
        }

        $ids =
            $asignaciones
                ->pluck('id')
                ->map(
                    fn ($id) =>
                        (int)
                        $id
                )
                ->all();

        $viajes =
            DB::table(
                'vehiculo_chofer_ruta as vcr'
            )
                ->join(
                    'ruta as r',
                    'r.id',
                    '=',
                    'vcr.id_ruta'
                )
                ->whereIn(
                    'vcr.id_asignacion_vehiculo_chofer',
                    $ids
                )
                ->select([
                    'vcr.id',
                    'vcr.id_asignacion_vehiculo_chofer',
                    'vcr.hora_inicio',
                    'vcr.created_at',

                    'r.id as id_ruta',
                    'r.origen',
                    'r.destino',
                    'r.tarifa',
                    'r.estado as estado_ruta',
                ])
                ->orderByDesc(
                    'vcr.hora_inicio'
                )
                ->get()
                ->groupBy(
                    'id_asignacion_vehiculo_chofer'
                );

        return $asignaciones
            ->map(
                function (
                    object $asignacion
                ) use (
                    $viajes
                ): array {
                    $items =
                        $viajes->get(
                            $asignacion->id,
                            collect()
                        );

                    return [
                        'id' =>
                            (int)
                            $asignacion->id,

                        'estado' =>
                            mb_strtoupper(
                                (string)
                                $asignacion->estado
                            ),

                        'fecha_asignacion' =>
                            $asignacion->created_at,

                        'fecha_actualizacion' =>
                            $asignacion->updated_at,

                        'fecha_eliminacion' =>
                            $asignacion->deleted_at,

                        'vehiculo' => [
                            'id' =>
                                (int)
                                $asignacion->id_vehiculo,

                            'placa' =>
                                $asignacion->placa,

                            'tipo' =>
                                $asignacion->tipo,

                            'marca' =>
                                $asignacion->marca,

                            'modelo' =>
                                $asignacion->modelo,

                            'color' =>
                                $asignacion->color,

                            'estado' =>
                                $asignacion->estado_vehiculo,
                        ],

                        'viajes' =>
                            $items
                                ->map(
                                    fn (
                                        object $viaje
                                    ): array => [
                                        'id' =>
                                            (int)
                                            $viaje->id,

                                        'hora_inicio' =>
                                            $viaje->hora_inicio,

                                        'created_at' =>
                                            $viaje->created_at,

                                        'ruta' => [
                                            'id' =>
                                                (int)
                                                $viaje->id_ruta,

                                            'origen' =>
                                                $viaje->origen,

                                            'destino' =>
                                                $viaje->destino,

                                            'tarifa' =>
                                                (float)
                                                $viaje->tarifa,

                                            'estado' =>
                                                $viaje->estado_ruta,
                                        ],
                                    ]
                                )
                                ->values()
                                ->all(),
                    ];
                }
            )
            ->values()
            ->all();
    }

    private function partirNombreCompleto(
        string $nombre
    ): array {
        $nombre =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $nombre
                )
            ) ?? '';

        $partes =
            preg_split(
                '/\s+/u',
                $nombre,
                -1,
                PREG_SPLIT_NO_EMPTY
            ) ?: [];

        if (
            count($partes) === 0
        ) {
            throw new RuntimeException(
                'El nombre completo no es válido.'
            );
        }

        if (
            count($partes) === 1
        ) {
            return [
                mb_substr(
                    $partes[0],
                    0,
                    40
                ),

                '-',

                null,
            ];
        }

        if (
            count($partes) === 2
        ) {
            return [
                mb_substr(
                    $partes[0],
                    0,
                    40
                ),

                mb_substr(
                    $partes[1],
                    0,
                    50
                ),

                null,
            ];
        }

        $segundoApellido =
            array_pop(
                $partes
            );

        $primerApellido =
            array_pop(
                $partes
            );

        return [
            mb_substr(
                implode(
                    ' ',
                    $partes
                ),
                0,
                40
            ),

            mb_substr(
                (string)
                $primerApellido,
                0,
                50
            ),

            mb_substr(
                (string)
                $segundoApellido,
                0,
                50
            ),
        ];
    }

    private function generarUsuarioTecnico(
        string $ci
    ): string {
        return
            'chofer_' .
            substr(
                hash(
                    'sha256',
                    mb_strtolower(
                        trim(
                            $ci
                        )
                    )
                ),
                0,
                24
            );
    }

    private function estadoBase(
        string $estado
    ): string {
        return
            mb_strtoupper(
                trim(
                    $estado
                )
            ) ===
            'INACTIVO'
                ? 'Inactivo'
                : 'Activo';
    }

    private function snapshot(
        Chofer $chofer
    ): array {
        $usuario =
            $chofer->usuario;

        return [
            'id' =>
                $chofer->id,

            'carnet_sindical' =>
                $chofer->carnet_sindical,

            'ci' =>
                $usuario?->ci,

            'telefono' =>
                $usuario?->celular,

            'numero_licencia' =>
                $chofer->numero_licencia,

            'categoria_licencia' =>
                $chofer->categoria_licencia,

            'estado' =>
                $usuario?->estado,
        ];
    }

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
            !str_starts_with(
                $ruta,
                'fotos-choferes/'
            ) &&
            !str_starts_with(
                $ruta,
                'fotos-usuarios/'
            )
        ) {
            return;
        }

        $path =
            public_path(
                $ruta
            );

        if (
            File::isFile(
                $path
            )
        ) {
            File::delete(
                $path
            );
        }
    }
}