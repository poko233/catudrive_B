<?php

declare(strict_types=1);

namespace App\Modules\Empresa\Services;

use App\Shared\Models\Empresa;
use App\Shared\Services\AppCacheService;
use App\Shared\Services\ImageOptimizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EmpresaService
{
    private const IMAGE_FIELDS = [
        'logo_cuadrado' =>
            'logo_cuadrado',

        'logo_largo' =>
            'logo_largo',

        'icono' =>
            'icono',

        'banner' =>
            'baner_inicio',
    ];

    public function __construct(
        private readonly ImageOptimizerService $imageOptimizer,
        private readonly AppCacheService $cache
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listar empresas
    |--------------------------------------------------------------------------
    |
    | Este listado NO se cachea todavía porque admite:
    |
    | - paginación
    | - búsqueda
    | - filtro por estado
    |
    | Usar una única clave de caché podría mezclar resultados de diferentes
    | páginas o filtros.
    |
    */

    public function listar(
        array $filtros = []
    ): LengthAwarePaginator {
        $porPagina =
            (int) (
                $filtros[
                    'por_pagina'
                ] ?? 15
            );

        $porPagina = max(
            1,
            min(
                $porPagina,
                100
            )
        );

        $estado =
            isset(
                $filtros['estado']
            )
                ? trim(
                    (string)
                    $filtros['estado']
                )
                : null;

        $buscar =
            isset(
                $filtros['buscar']
            )
                ? trim(
                    (string)
                    $filtros['buscar']
                )
                : null;

        return Empresa::query()
            ->when(
                $estado,
                fn ($query) =>
                    $query->where(
                        'estado',
                        $estado
                    )
            )
            ->when(
                $buscar,
                function (
                    $query
                ) use (
                    $buscar
                ): void {
                    $query->where(
                        function (
                            $subquery
                        ) use (
                            $buscar
                        ): void {
                            $subquery
                                ->where(
                                    'empresa',
                                    'ilike',
                                    "%{$buscar}%"
                                )
                                ->orWhere(
                                    'sigla',
                                    'ilike',
                                    "%{$buscar}%"
                                );
                        }
                    );
                }
            )
            ->orderBy(
                'empresa'
            )
            ->paginate(
                $porPagina
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear empresa
    |--------------------------------------------------------------------------
    */

    public function crear(
        array $datos
    ): Empresa {
        $datos['estado'] ??=
            'Activo';

        $empresa =
            Empresa::query()
                ->create(
                    $datos
                );

        /*
         * La empresa principal podría cambiar.
         */

        $this->invalidarCacheEmpresa();

        return $empresa;
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar empresa
    |--------------------------------------------------------------------------
    */

    public function actualizar(
        Empresa $empresa,
        array $datos
    ): Empresa {
        $empresa->fill(
            $datos
        );

        $empresa->save();

        /*
         * Invalidar únicamente después de guardar
         * correctamente en PostgreSQL.
         */

        $this->invalidarCacheEmpresa();

        return $empresa->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar empresa
    |--------------------------------------------------------------------------
    */

    public function eliminar(
        Empresa $empresa
    ): void {
        $rutasImagenes = [
            $empresa->logo_cuadrado,
            $empresa->logo_largo,
            $empresa->baner_inicio,
            $empresa->icono,
        ];

        /*
        |--------------------------------------------------------------------------
        | Eliminar primero de PostgreSQL
        |--------------------------------------------------------------------------
        |
        | Si una FK impide eliminar la empresa, las imágenes
        | permanecen intactas.
        |
        */

        $empresa->delete();

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        |
        | PostgreSQL ya confirmó la eliminación.
        |
        */

        $this->invalidarCacheEmpresa();

        /*
        |--------------------------------------------------------------------------
        | Eliminar imágenes después
        |--------------------------------------------------------------------------
        */

        foreach (
            $rutasImagenes
            as $ruta
        ) {
            $this
                ->eliminarArchivoPublicoEmpresa(
                    is_string($ruta)
                        ? $ruta
                        : null
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Empresa principal
    |--------------------------------------------------------------------------
    |
    | Este GET sí es excelente candidato para caché:
    |
    | - no tiene filtros
    | - no tiene paginación
    | - todos los usuarios autorizados reciben el mismo resultado
    |
    */

    public function obtenerEmpresaPrincipal():
        ?Empresa
    {
        /** @var Empresa|null $empresa */
        $empresa =
            $this->cache->remember(
                AppCacheService::EMPRESA,
                static fn (): ?Empresa =>
                    Empresa::query()
                        ->orderByRaw(
                            "
                            CASE
                                WHEN estado = 'Activo'
                                THEN 0
                                ELSE 1
                            END
                            "
                        )
                        ->orderBy(
                            'id'
                        )
                        ->first()
            );

        return $empresa;
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar imagen
    |--------------------------------------------------------------------------
    */

    public function actualizarImagen(
        Empresa $empresa,
        string $tipo,
        UploadedFile $archivo
    ): Empresa {
        $campo =
            $this->resolveImageField(
                $tipo
            );

        $rutaAnterior =
            $empresa->getAttribute(
                $campo
            );

        /*
        |--------------------------------------------------------------------------
        | Procesar nueva imagen primero
        |--------------------------------------------------------------------------
        |
        | Si el procesamiento falla, la imagen anterior permanece intacta.
        |
        */

        $nombreBase =
            sprintf(
                'empresa_%d_%s_%s',
                $empresa->id,
                $campo,
                Str::uuid()
                    ->toString()
            );

        $nombreArchivo =
            $this
                ->imageOptimizer
                ->convertToWebP(
                    $archivo,
                    public_path(
                        'empresa'
                    ),
                    $nombreBase
                );

        $rutaNueva =
            'empresa/'
            . $nombreArchivo;

        /*
        |--------------------------------------------------------------------------
        | Actualizar PostgreSQL
        |--------------------------------------------------------------------------
        */

        $empresa->forceFill([
            $campo =>
                $rutaNueva,
        ])->save();

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheEmpresa();

        /*
        |--------------------------------------------------------------------------
        | Borrar imagen anterior
        |--------------------------------------------------------------------------
        */

        if (
            is_string(
                $rutaAnterior
            )
            &&
            $rutaAnterior !==
                $rutaNueva
        ) {
            $this
                ->eliminarArchivoPublicoEmpresa(
                    $rutaAnterior
                );
        }

        return $empresa->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar imagen
    |--------------------------------------------------------------------------
    */

    public function eliminarImagen(
        Empresa $empresa,
        string $tipo
    ): Empresa {
        $campo =
            $this->resolveImageField(
                $tipo
            );

        $rutaAnterior =
            $empresa->getAttribute(
                $campo
            );

        /*
        |--------------------------------------------------------------------------
        | Primero actualizar PostgreSQL
        |--------------------------------------------------------------------------
        */

        $empresa->forceFill([
            $campo =>
                null,
        ])->save();

        /*
        |--------------------------------------------------------------------------
        | Invalidar caché
        |--------------------------------------------------------------------------
        */

        $this->invalidarCacheEmpresa();

        /*
        |--------------------------------------------------------------------------
        | Después eliminar archivo físico
        |--------------------------------------------------------------------------
        */

        $this
            ->eliminarArchivoPublicoEmpresa(
                is_string(
                    $rutaAnterior
                )
                    ? $rutaAnterior
                    : null
            );

        return $empresa->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidación centralizada
    |--------------------------------------------------------------------------
    */

    private function invalidarCacheEmpresa(): void
    {
        $this->cache
            ->forgetEmpresa();
    }

    /*
    |--------------------------------------------------------------------------
    | Resolver campo de imagen
    |--------------------------------------------------------------------------
    */

    private function resolveImageField(
        string $tipo
    ): string {
        if (
            !isset(
                self::IMAGE_FIELDS[
                    $tipo
                ]
            )
        ) {
            throw new InvalidArgumentException(
                "Tipo de imagen no válido: {$tipo}"
            );
        }

        return self::IMAGE_FIELDS[
            $tipo
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar archivo físico
    |--------------------------------------------------------------------------
    |
    | Solo permite eliminar archivos dentro de:
    |
    | public/empresa
    |
    */

    private function eliminarArchivoPublicoEmpresa(
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

        /*
        |--------------------------------------------------------------------------
        | Obtener únicamente el nombre del archivo
        |--------------------------------------------------------------------------
        */

        $archivo =
            basename(
                $ruta
            );

        if (
            $archivo === ''
            ||
            $archivo === '.'
            ||
            $archivo === '..'
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Construir ruta segura
        |--------------------------------------------------------------------------
        */

        $destino =
            public_path(
                'empresa/'
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