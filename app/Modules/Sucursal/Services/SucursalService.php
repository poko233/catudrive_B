<?php

declare(strict_types=1);

namespace App\Modules\Sucursal\Services;

use App\Shared\Models\Empresa;
use App\Shared\Models\Sucursal;
use App\Shared\Models\User;
use App\Shared\Services\AppCacheService;
use App\Shared\Services\ImageOptimizerService;
use App\Shared\Traits\GeneraUrlArchivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SucursalService
{
    use GeneraUrlArchivo;

    /*
    |--------------------------------------------------------------------------
    | Directorio oficial de imágenes
    |--------------------------------------------------------------------------
    */

    private const IMAGE_DIRECTORY =
        'empresa/sucursales';

    public function __construct(
        private readonly ImageOptimizerService $imageOptimizer,
        private readonly AppCacheService $cache
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listar sucursales
    |--------------------------------------------------------------------------
    |
    | No se cachea porque utiliza:
    |
    | - búsqueda
    | - filtros
    | - paginación
    |
    */

    public function listar(
        array $filtros = []
    ): LengthAwarePaginator {
        $query =
            Sucursal::query()
                ->with(
                    'empresa:id,empresa'
                );

        /*
        |--------------------------------------------------------------------------
        | Empresa
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $filtros['id_empresa']
            )
        ) {
            $query->where(
                'id_empresa',
                (int)
                $filtros['id_empresa']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $filtros['estado']
            )
        ) {
            $query->where(
                'estado',
                trim(
                    (string)
                    $filtros['estado']
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Búsqueda
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $filtros['buscar']
            )
        ) {
            $buscar =
                trim(
                    (string)
                    $filtros['buscar']
                );

            $query->where(
                function ($q) use ($buscar): void {
                    $q
                        ->where(
                            'sucursal',
                            'ilike',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'direccion',
                            'ilike',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'telefono',
                            'ilike',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'celular',
                            'ilike',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'email',
                            'ilike',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'ciudad',
                            'ilike',
                            "%{$buscar}%"
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Paginación
        |--------------------------------------------------------------------------
        */

        $porPagina =
            (int) (
                $filtros[
                    'por_pagina'
                ] ?? 15
            );

        $porPagina =
            max(
                1,
                min(
                    $porPagina,
                    100
                )
            );

        return $query
            ->orderBy(
                'sucursal'
            )
            ->paginate(
                $porPagina
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Obtener detalle
    |--------------------------------------------------------------------------
    |
    | Mantiene la carga de relaciones fuera del Controller.
    |
    */

    public function obtenerDetalle(
        Sucursal $sucursal
    ): Sucursal {
        return $sucursal->load(
            'empresa:id,empresa'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sucursales del usuario
    |--------------------------------------------------------------------------
    |
    | Esta información se cachea por usuario:
    |
    | metasoft:usuario:15:sucursales
    |
    */

    public function listarSucursalesUsuario(
        User $user
    ): Collection {
        /** @var Collection $sucursales */
        $sucursales =
            $this->cache
                ->rememberForUser(
                    (int) $user->id,
                    'sucursales',
                    fn (): Collection =>
                        $user
                            ->sucursales()
                            ->where(
                                'sucursal.estado',
                                'Activo'
                            )
                            ->orderBy(
                                'sucursal.sucursal'
                            )
                            ->get([
                                'sucursal.id',
                                'sucursal.id_empresa',
                                'sucursal.sucursal',
                                'sucursal.direccion',
                                'sucursal.ciudad',
                                'sucursal.estado',
                                'sucursal.imagen',
                            ])
                );

        return $sucursales;
    }

    /*
    |--------------------------------------------------------------------------
    | Sucursales activas por empresa
    |--------------------------------------------------------------------------
    |
    | Cache independiente por empresa:
    |
    | metasoft:sucursales:empresa:1:activas
    | metasoft:sucursales:empresa:2:activas
    |
    */

    public function listarActivasPorEmpresa(
        int $idEmpresa
    ): Collection {
        /*
        |--------------------------------------------------------------------------
        | Validar empresa
        |--------------------------------------------------------------------------
        |
        | La validación pertenece al Service y no al Controller.
        |
        | Aunque las sucursales estén cacheadas, seguimos verificando
        | que la empresa solicitada realmente exista.
        |
        */

        Empresa::query()
            ->findOrFail(
                $idEmpresa
            );

        /*
        |--------------------------------------------------------------------------
        | Obtener desde caché
        |--------------------------------------------------------------------------
        */

        /** @var Collection $sucursales */
        $sucursales =
            $this->cache->remember(
                $this->cacheKeyEmpresa(
                    $idEmpresa
                ),
                function () use (
                    $idEmpresa
                ): Collection {
                    return Sucursal::deEmpresa(
                        $idEmpresa
                    )
                        ->where(
                            'estado',
                            'Activo'
                        )
                        ->orderBy(
                            'sucursal'
                        )
                        ->get([
                            'id',
                            'id_empresa',
                            'sucursal',
                            'direccion',
                            'ciudad',
                            'estado',
                            'imagen',
                        ])
                        ->map(
                            fn (Sucursal $sucursal) => [
                                'id' =>
                                    (int) $sucursal->id,

                                'id_empresa' =>
                                    (int)
                                    $sucursal->id_empresa,

                                'sucursal' =>
                                    $sucursal->sucursal,

                                'direccion' =>
                                    $sucursal->direccion,

                                'ciudad' =>
                                    $sucursal->ciudad,

                                'estado' =>
                                    $sucursal->estado,

                                'imagen' =>
                                    $sucursal->imagen,

                                'imagenUrl' =>
                                    $this->urlImagen(
                                        $sucursal->imagen
                                    ),
                            ]
                        );
                }
            );

        return $sucursales;
    }

    /*
    |--------------------------------------------------------------------------
    | Crear sucursal
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $datos
    ): Sucursal {
        /*
        |--------------------------------------------------------------------------
        | Imagen
        |--------------------------------------------------------------------------
        */

        $archivo =
            $datos['imagen']
            ?? null;

        unset(
            $datos['imagen']
        );

        /*
        |--------------------------------------------------------------------------
        | Estado por defecto
        |--------------------------------------------------------------------------
        */

        $datos['estado'] ??=
            'Activo';

        $rutaNueva =
            null;

        /*
        |--------------------------------------------------------------------------
        | Procesar imagen primero
        |--------------------------------------------------------------------------
        */

        if (
            $archivo instanceof UploadedFile
        ) {
            $rutaNueva =
                $this->guardarImagen(
                    $archivo
                );

            $datos['imagen'] =
                $rutaNueva;
        }

        /*
        |--------------------------------------------------------------------------
        | Crear en PostgreSQL
        |--------------------------------------------------------------------------
        */

        try {
            $sucursal =
                DB::transaction(
                    fn () =>
                        Sucursal::query()
                            ->create(
                                $datos
                            )
                );
        } catch (
            Throwable $exception
        ) {
            /*
             * Si PostgreSQL falla,
             * eliminar la imagen recién creada.
             */

            if (
                $rutaNueva
            ) {
                $this->eliminarImagenLocal(
                    $rutaNueva
                );
            }

            throw $exception;
        }

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheEmpresa(
            (int) $sucursal->id_empresa
        );

        return $sucursal
            ->fresh()
            ->load(
                'empresa:id,empresa'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar sucursal
    |--------------------------------------------------------------------------
    */

    public function actualizar(
        Sucursal $sucursal,
        array $datos
    ): Sucursal {
        /*
        |--------------------------------------------------------------------------
        | Contexto anterior
        |--------------------------------------------------------------------------
        */

        $idEmpresaAnterior =
            (int) $sucursal->id_empresa;

        $usuariosAfectados =
            $this->usuariosDeSucursal(
                (int) $sucursal->id
            );

        /*
        |--------------------------------------------------------------------------
        | Imagen enviada
        |--------------------------------------------------------------------------
        */

        $archivo =
            $datos['imagen']
            ?? null;

        unset(
            $datos['imagen']
        );

        /*
        |--------------------------------------------------------------------------
        | Imagen anterior
        |--------------------------------------------------------------------------
        */

        $rutaAnterior =
            is_string(
                $sucursal->imagen
            )
                ? $sucursal->imagen
                : null;

        $rutaNueva =
            null;

        /*
        |--------------------------------------------------------------------------
        | Procesar nueva imagen
        |--------------------------------------------------------------------------
        */

        if (
            $archivo instanceof UploadedFile
        ) {
            $rutaNueva =
                $this->guardarImagen(
                    $archivo
                );

            $datos['imagen'] =
                $rutaNueva;
        }

        /*
        |--------------------------------------------------------------------------
        | Actualizar PostgreSQL
        |--------------------------------------------------------------------------
        */

        try {
            DB::transaction(
                function () use (
                    $sucursal,
                    $datos
                ): void {
                    $sucursal->fill(
                        $datos
                    );

                    $sucursal->save();
                }
            );
        } catch (
            Throwable $exception
        ) {
            /*
             * Si PostgreSQL falla,
             * eliminar solamente la imagen nueva.
             */

            if (
                $rutaNueva
            ) {
                $this->eliminarImagenLocal(
                    $rutaNueva
                );
            }

            throw $exception;
        }

        /*
        |--------------------------------------------------------------------------
        | Refrescar
        |--------------------------------------------------------------------------
        */

        $sucursal->refresh();

        $idEmpresaNueva =
            (int) $sucursal->id_empresa;

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché por empresa
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheEmpresa(
            $idEmpresaAnterior
        );

        if (
            $idEmpresaNueva !==
            $idEmpresaAnterior
        ) {
            $this->invalidarCacheEmpresa(
                $idEmpresaNueva
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché de usuarios
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheUsuarios(
            $usuariosAfectados
        );

        /*
        |--------------------------------------------------------------------------
        | Eliminar imagen anterior
        |--------------------------------------------------------------------------
        */

        if (
            $rutaNueva !== null &&
            $rutaAnterior !==
                $rutaNueva
        ) {
            $this->eliminarImagenLocal(
                $rutaAnterior
            );
        }

        return $sucursal
            ->load(
                'empresa:id,empresa'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar sucursal
    |--------------------------------------------------------------------------
    */

    public function eliminar(
        Sucursal $sucursal
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Usuarios asignados
        |--------------------------------------------------------------------------
        */

        if (
            $sucursal
                ->users()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'sucursal' => [
                    'No se puede eliminar una sucursal que tiene usuarios asignados.',
                ],
            ]);
        }

        $idEmpresa =
            (int) $sucursal->id_empresa;

        /*
        |--------------------------------------------------------------------------
        | Imagen actual
        |--------------------------------------------------------------------------
        */

        $rutaAnterior =
            is_string(
                $sucursal->imagen
            )
                ? $sucursal->imagen
                : null;

        /*
        |--------------------------------------------------------------------------
        | Eliminar PostgreSQL
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $sucursal
            ): void {
                $sucursal->delete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheEmpresa(
            $idEmpresa
        );

        /*
        |--------------------------------------------------------------------------
        | Eliminar archivo
        |--------------------------------------------------------------------------
        */

        $this->eliminarImagenLocal(
            $rutaAnterior
        );
    }

    /*
    |--------------------------------------------------------------------------
    | URL pública
    |--------------------------------------------------------------------------
    */

    public function urlImagen(
        ?string $ruta
    ): ?string {
        if (
            !$ruta
        ) {
            return null;
        }

        return $this->urlArchivo(
            'storage/'
            . ltrim(
                $ruta,
                '/'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Usuarios relacionados
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, int>
     */
    private function usuariosDeSucursal(
        int $idSucursal
    ): array {
        return DB::table(
            'user_sucursal'
        )
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->pluck(
                'id_user'
            )
            ->map(
                fn ($id) =>
                    (int) $id
            )
            ->unique()
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar caché por usuario
    |--------------------------------------------------------------------------
    */

    /**
     * @param array<int, int> $usuarios
     */
    private function invalidarCacheUsuarios(
        array $usuarios
    ): void {
        foreach (
            $usuarios
            as $idUsuario
        ) {
            $this->cache
                ->forgetUserSucursales(
                    (int) $idUsuario
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidar caché por empresa
    |--------------------------------------------------------------------------
    */

    private function invalidarCacheEmpresa(
        int $idEmpresa
    ): void {
        $this->cache->forget(
            $this->cacheKeyEmpresa(
                $idEmpresa
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Clave de caché por empresa
    |--------------------------------------------------------------------------
    */

    private function cacheKeyEmpresa(
        int $idEmpresa
    ): string {
        return sprintf(
            'sucursales:empresa:%d:activas',
            $idEmpresa
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar imagen
    |--------------------------------------------------------------------------
    */

    private function guardarImagen(
        UploadedFile $archivo
    ): string {
        $directorio =
            storage_path(
                'app/public/'
                . self::IMAGE_DIRECTORY
            );

        $nombreBase =
            'sucursal_'
            . Str::lower(
                Str::random(
                    24
                )
            );

        $nombreArchivo =
            $this
                ->imageOptimizer
                ->convertToWebP(
                    $archivo,
                    $directorio,
                    $nombreBase
                );

        return self::IMAGE_DIRECTORY
            . '/'
            . $nombreArchivo;
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar imagen local
    |--------------------------------------------------------------------------
    */

    private function eliminarImagenLocal(
        ?string $ruta
    ): void {
        if (
            !$ruta
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalizar
        |--------------------------------------------------------------------------
        */

        $ruta =
            str_replace(
                '\\',
                '/',
                trim(
                    $ruta
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Ignorar URLs externas
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with(
                $ruta,
                'http://'
            )
            ||
            str_starts_with(
                $ruta,
                'https://'
            )
            ||
            str_starts_with(
                $ruta,
                'data:image/'
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Nombre seguro
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Ruta segura
        |--------------------------------------------------------------------------
        */

        $rutaSegura =
            self::IMAGE_DIRECTORY
            . '/'
            . $archivo;

        /*
        |--------------------------------------------------------------------------
        | Eliminar
        |--------------------------------------------------------------------------
        */

        if (
            Storage::disk(
                'public'
            )->exists(
                $rutaSegura
            )
        ) {
            Storage::disk(
                'public'
            )->delete(
                $rutaSegura
            );
        }
    }
}