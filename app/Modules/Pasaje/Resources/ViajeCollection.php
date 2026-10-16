<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ViajeCollection extends ResourceCollection
{
    /**
     * Recurso que se aplicará a cada item.
     */
    public $collects = ViajeResource::class;

    /**
     * Metadatos extra a inyectar en "meta".
     *
     * @var array<string, mixed>
     */
    private array $metaExtra = [];

    /**
     * Inyecta claves adicionales dentro de meta.
     *
     * @param array<string, mixed> $meta
     */
    public function withMeta(array $meta): static
    {
        $this->metaExtra = $meta;

        return $this;
    }

    /**
     * Añade los metadatos extra al bloque "meta" que genera Laravel
     * por defecto para respuestas paginadas.
     *
     * @param  array<string, mixed> $paginated
     * @param  array<string, mixed> $default
     * @return array<string, mixed>
     */
    public function paginationInformation(
        Request $request,
        array $paginated,
        array $default
    ): array {
        $default['meta'] = array_merge(
            $default['meta'] ?? [],
            $this->metaExtra
        );

        return $default;
    }
}