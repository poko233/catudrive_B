<?php

declare(strict_types=1);

namespace App\Modules\AsignacionVehiculo\Services;

use App\Shared\Models\AsignacionVehiculoChofer;
use App\Shared\Models\Chofer;
use App\Shared\Models\Vehiculo;
use App\Shared\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignacionVehiculoService
{
    public function __construct(
        private readonly AuditService $audit
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BASE
    |--------------------------------------------------------------------------
    */

    private function queryBase(): Builder
    {
        return AsignacionVehiculoChofer::query()
            ->with([
                'chofer.usuario',
                'vehiculo',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function listar(): Collection
    {
        return $this
            ->queryBase()
            ->orderByRaw(
                "
                CASE
                    WHEN estado = 'Activo'
                    THEN 0
                    ELSE 1
                END
                "
            )
            ->orderByDesc(
                'fecha_asignacion'
            )
            ->orderByDesc(
                'id'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORIAL
    |--------------------------------------------------------------------------
    */

    public function historial(): Collection
    {
        return $this
            ->queryBase()
            ->orderByDesc(
                'fecha_asignacion'
            )
            ->orderByDesc(
                'id'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | OBTENER
    |--------------------------------------------------------------------------
    */

    public function obtener(
        int $id
    ): AsignacionVehiculoChofer {
        return $this
            ->queryBase()
            ->findOrFail(
                $id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | CATÁLOGOS
    |--------------------------------------------------------------------------
    */

    public function catalogos(
        ?int $exceptoAsignacion = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | CHOFERES OCUPADOS
        |--------------------------------------------------------------------------
        */

        $choferesOcupados =
            AsignacionVehiculoChofer::query()
                ->where(
                    'estado',
                    'Activo'
                )
                ->when(
                    $exceptoAsignacion,
                    fn (Builder $query) =>
                        $query->where(
                            'id',
                            '!=',
                            $exceptoAsignacion
                        )
                )
                ->pluck(
                    'id_chofer'
                );

        /*
        |--------------------------------------------------------------------------
        | VEHÍCULOS OCUPADOS
        |--------------------------------------------------------------------------
        */

        $vehiculosOcupados =
            AsignacionVehiculoChofer::query()
                ->where(
                    'estado',
                    'Activo'
                )
                ->when(
                    $exceptoAsignacion,
                    fn (Builder $query) =>
                        $query->where(
                            'id',
                            '!=',
                            $exceptoAsignacion
                        )
                )
                ->pluck(
                    'id_vehiculo'
                );

        /*
        |--------------------------------------------------------------------------
        | CHOFERES DISPONIBLES
        |--------------------------------------------------------------------------
        */

        $choferes =
            Chofer::query()
                ->with(
                    'usuario'
                )
                ->whereNotIn(
                    'id',
                    $choferesOcupados
                )
                ->whereHas(
                    'usuario',
                    function (Builder $query): void {
                        $query->where(
                            'estado',
                            'Activo'
                        );
                    }
                )
                ->get()
                ->map(
                    function (Chofer $chofer): array {
                        $usuario =
                            $chofer->usuario;

                        $nombre =
                            trim(
                                implode(
                                    ' ',
                                    array_filter([
                                        $usuario?->nombres,
                                        $usuario?->primer_apellido,
                                        $usuario?->segundo_apellido,
                                    ])
                                )
                            );

                        return [
                            'id' =>
                                (int)
                                $chofer->id,

                            'nombre' =>
                                $nombre,

                            'ci' =>
                                $usuario?->ci,

                            'carnet_sindical' =>
                                $chofer->carnet_sindical,

                            'numero_licencia' =>
                                $chofer->numero_licencia,

                            'categoria_licencia' =>
                                $chofer->categoria_licencia,
                        ];
                    }
                )
                ->sortBy(
                    fn (array $item) =>
                        mb_strtolower(
                            $item['nombre']
                        )
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | VEHÍCULOS DISPONIBLES
        |--------------------------------------------------------------------------
        */

        $vehiculos =
            Vehiculo::query()
                ->whereNotIn(
                    'id',
                    $vehiculosOcupados
                )
                ->where(
                    'estado',
                    'Operativo'
                )
                ->orderBy(
                    'placa'
                )
                ->get()
                ->map(
                    fn (Vehiculo $vehiculo): array => [
                        'id' =>
                            (int)
                            $vehiculo->id,

                        'placa' =>
                            $vehiculo->placa,

                        'tipo' =>
                            $vehiculo->tipo,

                        'marca' =>
                            $vehiculo->marca,

                        'modelo' =>
                            $vehiculo->modelo,

                        'color' =>
                            $vehiculo->color,

                        'capacidad' =>
                            (int)
                            $vehiculo->capacidad,

                        'estado' =>
                            $vehiculo->estado,
                    ]
                )
                ->values();

        return [
            'choferes' =>
                $choferes,

            'vehiculos' =>
                $vehiculos,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $data
    ): AsignacionVehiculoChofer {
        $id =
            0;

        DB::transaction(
            function () use (
                $data,
                &$id
            ): void {
                $idChofer =
                    (int)
                    $data[
                        'id_chofer'
                    ];

                $idVehiculo =
                    (int)
                    $data[
                        'id_vehiculo'
                    ];

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR RECURSOS
                |--------------------------------------------------------------------------
                */

                $this->bloquearRecursos(
                    $idChofer,
                    $idVehiculo
                );

                /*
                |--------------------------------------------------------------------------
                | VALIDAR DISPONIBILIDAD
                |--------------------------------------------------------------------------
                */

                $this->validarDisponibilidad(
                    $idChofer,
                    $idVehiculo
                );

                /*
                |--------------------------------------------------------------------------
                | CREAR
                |--------------------------------------------------------------------------
                */

                $asignacion =
                    AsignacionVehiculoChofer::query()
                        ->create([
                            'id_chofer' =>
                                $idChofer,

                            'id_vehiculo' =>
                                $idVehiculo,

                            'fecha_asignacion' =>
                                $data[
                                    'fecha_asignacion'
                                ],

                            'fecha_finalizacion' =>
                                null,

                            'observacion' =>
                                $data[
                                    'observacion'
                                ] ?? null,

                            'estado' =>
                                'Activo',
                        ]);

                $id =
                    (int)
                    $asignacion->id;
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        $this->audit
            ->created(
                resource:
                    'AsignacionVehiculo',

                resourceId:
                    $id,

                after:
                    $this->snapshot(
                        $fresh
                    ),
            );

        return $fresh;
    }

    /*
    |--------------------------------------------------------------------------
    | CAMBIO DE ASIGNACIÓN
    |--------------------------------------------------------------------------
    |
    | MUY IMPORTANTE:
    |
    | No sobrescribimos el histórico.
    |
    | Ejemplo:
    |
    | Luis -> Bus 01
    |
    | pasa a:
    |
    | Inactivo
    |
    | y se crea:
    |
    | Luis -> Bus 04
    |
    | Activo
    |
    */

    public function cambiar(
        int $id,
        array $data
    ): array {
        $nuevaId =
            0;

        $anteriorSnapshot =
            [];

        DB::transaction(
            function () use (
                $id,
                $data,
                &$nuevaId,
                &$anteriorSnapshot
            ): void {
                /** @var AsignacionVehiculoChofer $actual */
                $actual =
                    AsignacionVehiculoChofer::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                if (
                    !$actual->estaActiva()
                ) {
                    throw ValidationException::withMessages([
                        'asignacion' =>
                            'Solo se puede cambiar una asignación activa.',
                    ]);
                }

                $fechaNueva =
                    (string)
                    $data[
                        'fecha_asignacion'
                    ];

                $fechaActual =
                    $actual
                        ->fecha_asignacion
                        ?->format(
                            'Y-m-d'
                        );

                if (
                    $fechaActual &&
                    $fechaNueva <
                    $fechaActual
                ) {
                    throw ValidationException::withMessages([
                        'fecha_asignacion' =>
                            'La fecha del cambio no puede ser anterior a la asignación actual.',
                    ]);
                }

                $idChofer =
                    (int)
                    $data[
                        'id_chofer'
                    ];

                $idVehiculo =
                    (int)
                    $data[
                        'id_vehiculo'
                    ];

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR NUEVOS RECURSOS
                |--------------------------------------------------------------------------
                */

                $this->bloquearRecursos(
                    $idChofer,
                    $idVehiculo
                );

                /*
                |--------------------------------------------------------------------------
                | VALIDAR DISPONIBILIDAD
                |--------------------------------------------------------------------------
                */

                $this->validarDisponibilidad(
                    $idChofer,
                    $idVehiculo,
                    $id
                );

                /*
                |--------------------------------------------------------------------------
                | SNAPSHOT ANTERIOR
                |--------------------------------------------------------------------------
                */

                $anteriorSnapshot =
                    $this->snapshot(
                        $actual
                    );

                /*
                |--------------------------------------------------------------------------
                | FINALIZAR ACTUAL
                |--------------------------------------------------------------------------
                */

                $actual->estado =
                    'Inactivo';

                $actual->fecha_finalizacion =
                    $fechaNueva;

                $actual->save();

                /*
                |--------------------------------------------------------------------------
                | NUEVA ASIGNACIÓN
                |--------------------------------------------------------------------------
                */

                $nueva =
                    AsignacionVehiculoChofer::query()
                        ->create([
                            'id_chofer' =>
                                $idChofer,

                            'id_vehiculo' =>
                                $idVehiculo,

                            'fecha_asignacion' =>
                                $fechaNueva,

                            'fecha_finalizacion' =>
                                null,

                            'observacion' =>
                                $data[
                                    'observacion'
                                ] ?? null,

                            'estado' =>
                                'Activo',
                        ]);

                $nuevaId =
                    (int)
                    $nueva->id;
            }
        );

        $anterior =
            $this->obtener(
                $id
            );

        $nueva =
            $this->obtener(
                $nuevaId
            );

        /*
        |--------------------------------------------------------------------------
        | AUDITORÍA ANTERIOR
        |--------------------------------------------------------------------------
        */

        $this->audit
            ->updated(
                resource:
                    'AsignacionVehiculo',

                resourceId:
                    $id,

                before:
                    $anteriorSnapshot,

                after:
                    $this->snapshot(
                        $anterior
                    ),
            );

        /*
        |--------------------------------------------------------------------------
        | AUDITORÍA NUEVA
        |--------------------------------------------------------------------------
        */

        $this->audit
            ->created(
                resource:
                    'AsignacionVehiculo',

                resourceId:
                    $nuevaId,

                after:
                    $this->snapshot(
                        $nueva
                    ),
            );

        return [
            'anterior' =>
                $anterior,

            'asignacion' =>
                $nueva,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FINALIZAR
    |--------------------------------------------------------------------------
    */

    public function finalizar(
        int $id,
        string $fechaFinalizacion
    ): AsignacionVehiculoChofer {
        $before =
            [];

        DB::transaction(
            function () use (
                $id,
                $fechaFinalizacion,
                &$before
            ): void {
                /** @var AsignacionVehiculoChofer $asignacion */
                $asignacion =
                    AsignacionVehiculoChofer::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id
                        );

                if (
                    !$asignacion->estaActiva()
                ) {
                    throw ValidationException::withMessages([
                        'asignacion' =>
                            'La asignación ya se encuentra finalizada.',
                    ]);
                }

                $inicio =
                    $asignacion
                        ->fecha_asignacion
                        ?->format(
                            'Y-m-d'
                        );

                if (
                    $inicio &&
                    $fechaFinalizacion <
                    $inicio
                ) {
                    throw ValidationException::withMessages([
                        'fecha_finalizacion' =>
                            'La fecha de finalización no puede ser anterior a la fecha de asignación.',
                    ]);
                }

                $before =
                    $this->snapshot(
                        $asignacion
                    );

                $asignacion->estado =
                    'Inactivo';

                $asignacion->fecha_finalizacion =
                    $fechaFinalizacion;

                $asignacion->save();
            }
        );

        $fresh =
            $this->obtener(
                $id
            );

        $this->audit
            ->updated(
                resource:
                    'AsignacionVehiculo',

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

    /*
    |--------------------------------------------------------------------------
    | BLOQUEAR CHOFER Y VEHÍCULO
    |--------------------------------------------------------------------------
    |
    | Reduce problemas de concurrencia:
    |
    | dos usuarios intentando asignar
    | simultáneamente el mismo recurso.
    |
    */

    private function bloquearRecursos(
        int $idChofer,
        int $idVehiculo
    ): void {
        Chofer::query()
            ->where(
                'id',
                $idChofer
            )
            ->lockForUpdate()
            ->firstOrFail();

        Vehiculo::query()
            ->where(
                'id',
                $idVehiculo
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR DISPONIBILIDAD
    |--------------------------------------------------------------------------
    */

    private function validarDisponibilidad(
        int $idChofer,
        int $idVehiculo,
        ?int $exceptoAsignacion = null
    ): void {
        /*
        |--------------------------------------------------------------------------
        | CHOFER
        |--------------------------------------------------------------------------
        */

        $chofer =
            Chofer::query()
                ->with(
                    'usuario'
                )
                ->findOrFail(
                    $idChofer
                );

        if (
            !$chofer->usuario ||
            mb_strtoupper(
                trim(
                    (string)
                    $chofer->usuario->estado
                )
            ) !== 'ACTIVO'
        ) {
            throw ValidationException::withMessages([
                'id_chofer' =>
                    'El chofer seleccionado no se encuentra activo.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | VEHÍCULO
        |--------------------------------------------------------------------------
        */

        $vehiculo =
            Vehiculo::query()
                ->findOrFail(
                    $idVehiculo
                );

        if (
            mb_strtoupper(
                trim(
                    (string)
                    $vehiculo->estado
                )
            ) !== 'OPERATIVO'
        ) {
            throw ValidationException::withMessages([
                'id_vehiculo' =>
                    'El vehículo seleccionado no se encuentra operativo.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CHOFER YA OCUPADO
        |--------------------------------------------------------------------------
        */

        $choferOcupado =
            AsignacionVehiculoChofer::query()
                ->where(
                    'id_chofer',
                    $idChofer
                )
                ->where(
                    'estado',
                    'Activo'
                )
                ->when(
                    $exceptoAsignacion,
                    fn (Builder $query) =>
                        $query->where(
                            'id',
                            '!=',
                            $exceptoAsignacion
                        )
                )
                ->exists();

        if (
            $choferOcupado
        ) {
            throw ValidationException::withMessages([
                'id_chofer' =>
                    'El chofer ya tiene un vehículo asignado.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | VEHÍCULO YA OCUPADO
        |--------------------------------------------------------------------------
        */

        $vehiculoOcupado =
            AsignacionVehiculoChofer::query()
                ->where(
                    'id_vehiculo',
                    $idVehiculo
                )
                ->where(
                    'estado',
                    'Activo'
                )
                ->when(
                    $exceptoAsignacion,
                    fn (Builder $query) =>
                        $query->where(
                            'id',
                            '!=',
                            $exceptoAsignacion
                        )
                )
                ->exists();

        if (
            $vehiculoOcupado
        ) {
            throw ValidationException::withMessages([
                'id_vehiculo' =>
                    'El vehículo ya se encuentra asignado a otro chofer.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SNAPSHOT
    |--------------------------------------------------------------------------
    */

    private function snapshot(
        AsignacionVehiculoChofer $asignacion
    ): array {
        return [
            'id' =>
                (int)
                $asignacion->id,

            'id_chofer' =>
                (int)
                $asignacion->id_chofer,

            'id_vehiculo' =>
                (int)
                $asignacion->id_vehiculo,

            'fecha_asignacion' =>
                $asignacion
                    ->fecha_asignacion
                    ?->format(
                        'Y-m-d'
                    ),

            'fecha_finalizacion' =>
                $asignacion
                    ->fecha_finalizacion
                    ?->format(
                        'Y-m-d'
                    ),

            'observacion' =>
                $asignacion->observacion,

            'estado' =>
                $asignacion->estado,
        ];
    }
}