<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

final class AppCacheService
{
    /*
    |--------------------------------------------------------------------------
    | Claves globales
    |--------------------------------------------------------------------------
    */

    public const MODULOS =
        'catalogo:modulos';

    public const FORMULARIOS =
        'catalogo:formularios';

    public const EMPRESA =
        'catalogo:empresa';

    public const ROLES =
        'catalogo:roles';

    public const ACCIONES =
        'catalogo:acciones';

    public const ROLES_PERMISOS =
        'roles:permisos';

    public const FORMULARIO_ACCIONES =
        'formulario:acciones';

    /*
    |--------------------------------------------------------------------------
    | Remember seguro
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    |
    | Este servicio NO almacena objetos PHP en caché.
    |
    | Especialmente evitamos:
    |
    | - Eloquent Model
    | - Eloquent Collection
    | - Support Collection
    | - ResourceCollection
    | - objetos serializados
    |
    | ¿Por qué?
    |
    | Porque al modificar namespaces, mover modelos o actualizar código,
    | PHP puede intentar deserializar una clase que ya no existe y devolver:
    |
    | __PHP_Incomplete_Class
    |
    | Solo persistimos estructuras estables:
    |
    | - string
    | - int
    | - float
    | - bool
    | - array
    |
    | Los arrays también se comprueban recursivamente para impedir que
    | contengan objetos internamente.
    |
    */

    public function remember(
        string $key,
        Closure $callback,
        ?int $ttl = null
    ): mixed {
        $key =
            $this->buildKey(
                $key
            );

        $ttl ??=
            $this->defaultTtl();

        /*
        |--------------------------------------------------------------------------
        | Intentar leer caché existente
        |--------------------------------------------------------------------------
        */

        $found =
            false;

        $cached =
            $this->readSafeCache(
                $key,
                $found
            );

        if ($found) {
            return $cached;
        }

        /*
        |--------------------------------------------------------------------------
        | Evitar cache stampede
        |--------------------------------------------------------------------------
        */

        try {
            return Cache::lock(
                "{$key}:lock",
                10
            )->block(
                5,
                function () use (
                    $key,
                    $ttl,
                    $callback
                ): mixed {
                    /*
                     * Puede que otra request haya guardado
                     * el valor mientras esperábamos el lock.
                     */

                    $found =
                        false;

                    $cached =
                        $this->readSafeCache(
                            $key,
                            $found
                        );

                    if ($found) {
                        return $cached;
                    }

                    return $this
                        ->resolveAndStore(
                            $key,
                            $ttl,
                            $callback
                        );
                }
            );
        } catch (
            LockTimeoutException
        ) {
            /*
             * Si no conseguimos el lock dentro
             * del tiempo definido, continuamos.
             *
             * La API nunca debe romperse
             * solamente por un lock de caché.
             */

            $found =
                false;

            $cached =
                $this->readSafeCache(
                    $key,
                    $found
                );

            if ($found) {
                return $cached;
            }

            return $this
                ->resolveAndStore(
                    $key,
                    $ttl,
                    $callback
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Resolver callback y guardar si corresponde
    |--------------------------------------------------------------------------
    */

    private function resolveAndStore(
        string $key,
        int $ttl,
        Closure $callback
    ): mixed {
        $value =
            $callback();

        /*
         * Solo persistimos valores seguros.
         *
         * Si es un Model o Collection:
         *
         * - se devuelve normalmente;
         * - NO se escribe en caché.
         */

        if (
            $this->isSafeCacheValue(
                $value
            )
        ) {
            Cache::put(
                $key,
                $value,
                $ttl
            );
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Leer una entrada segura
    |--------------------------------------------------------------------------
    |
    | Si encontramos una caché vieja con:
    |
    | __PHP_Incomplete_Class
    |
    | o cualquier otro objeto, se elimina automáticamente.
    |
    */

    private function readSafeCache(
        string $key,
        bool &$found
    ): mixed {
        $found =
            false;

        if (
            !Cache::has(
                $key
            )
        ) {
            return null;
        }

        $value =
            Cache::get(
                $key
            );

        /*
         * Puede ser:
         *
         * __PHP_Incomplete_Class
         * Collection
         * Model
         * objeto antiguo
         * array conteniendo objetos
         *
         * En cualquiera de esos casos,
         * eliminamos la entrada.
         */

        if (
            !$this->isSafeCacheValue(
                $value
            )
        ) {
            Cache::forget(
                $key
            );

            return null;
        }

        $found =
            true;

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Comprobar si un valor es seguro
    |--------------------------------------------------------------------------
    */

    private function isSafeCacheValue(
        mixed $value
    ): bool {
        /*
         * Tipos simples.
         */

        if (
            is_string($value) ||
            is_int($value) ||
            is_float($value) ||
            is_bool($value)
        ) {
            return true;
        }

        /*
         * Null no necesita persistirse.
         */

        if ($value === null) {
            return false;
        }

        /*
         * Objetos nunca.
         *
         * Esto incluye:
         *
         * - Model
         * - Collection
         * - __PHP_Incomplete_Class
         * - Carbon
         * - Resource
         * etc.
         */

        if (
            is_object(
                $value
            )
        ) {
            return false;
        }

        /*
         * Resources tampoco.
         */

        if (
            is_resource(
                $value
            )
        ) {
            return false;
        }

        /*
         * Arrays:
         *
         * comprobamos recursivamente
         * todo su contenido.
         */

        if (
            is_array(
                $value
            )
        ) {
            foreach (
                $value
                as $item
            ) {
                if (
                    !$this->isSafeCacheValue(
                        $item
                    )
                ) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Forget
    |--------------------------------------------------------------------------
    */

    public function forget(
        string $key
    ): bool {
        return Cache::forget(
            $this->buildKey(
                $key
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Datos globales
    |--------------------------------------------------------------------------
    */

    public function forgetModulos(): bool
    {
        return $this->forget(
            self::MODULOS
        );
    }

    public function forgetFormularios(): bool
    {
        return $this->forget(
            self::FORMULARIOS
        );
    }

    public function forgetEmpresa(): bool
    {
        return $this->forget(
            self::EMPRESA
        );
    }

    public function forgetRoles(): bool
    {
        return $this->forget(
            self::ROLES
        );
    }

    public function forgetAcciones(): bool
    {
        return $this->forget(
            self::ACCIONES
        );
    }

    public function forgetRolesPermisos(): bool
    {
        return $this->forget(
            self::ROLES_PERMISOS
        );
    }

    public function forgetFormularioAcciones(): bool
    {
        return $this->forget(
            self::FORMULARIO_ACCIONES
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Caché por rol
    |--------------------------------------------------------------------------
    */

    public function roleKey(
        int $idRol,
        string $resource
    ): string {
        return sprintf(
            'rol:%d:%s',
            $idRol,
            trim(
                $resource,
                ':'
            )
        );
    }

    public function rememberForRole(
        int $idRol,
        string $resource,
        Closure $callback,
        ?int $ttl = null
    ): mixed {
        return $this->remember(
            $this->roleKey(
                $idRol,
                $resource
            ),
            $callback,
            $ttl
        );
    }

    public function forgetForRole(
        int $idRol,
        string $resource
    ): bool {
        return $this->forget(
            $this->roleKey(
                $idRol,
                $resource
            )
        );
    }

    public function forgetRolePermissions(
        int $idRol
    ): bool {
        return $this->forgetForRole(
            $idRol,
            'permisos'
        );
    }

    public function forgetRoleVisibility(
        int $idRol
    ): bool {
        return $this->forgetForRole(
            $idRol,
            'visibility'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Caché por usuario
    |--------------------------------------------------------------------------
    */

    public function userKey(
        int $idUsuario,
        string $resource
    ): string {
        return sprintf(
            'usuario:%d:%s',
            $idUsuario,
            trim(
                $resource,
                ':'
            )
        );
    }

    public function rememberForUser(
        int $idUsuario,
        string $resource,
        Closure $callback,
        ?int $ttl = null
    ): mixed {
        return $this->remember(
            $this->userKey(
                $idUsuario,
                $resource
            ),
            $callback,
            $ttl
        );
    }

    public function forgetForUser(
        int $idUsuario,
        string $resource
    ): bool {
        return $this->forget(
            $this->userKey(
                $idUsuario,
                $resource
            )
        );
    }

    public function forgetUserSucursales(
        int $idUsuario
    ): bool {
        return $this->forgetForUser(
            $idUsuario,
            'sucursales'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Usuario + sucursal
    |--------------------------------------------------------------------------
    */

    public function userSucursalKey(
        int $idUsuario,
        int $idSucursal,
        string $resource
    ): string {
        return sprintf(
            'usuario:%d:sucursal:%d:%s',
            $idUsuario,
            $idSucursal,
            trim(
                $resource,
                ':'
            )
        );
    }

    public function rememberForUserSucursal(
        int $idUsuario,
        int $idSucursal,
        string $resource,
        Closure $callback,
        ?int $ttl = null
    ): mixed {
        return $this->remember(
            $this->userSucursalKey(
                $idUsuario,
                $idSucursal,
                $resource
            ),
            $callback,
            $ttl
        );
    }

    public function forgetForUserSucursal(
        int $idUsuario,
        int $idSucursal,
        string $resource
    ): bool {
        return $this->forget(
            $this->userSucursalKey(
                $idUsuario,
                $idSucursal,
                $resource
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function buildKey(
        string $key
    ): string {
        $prefix =
            trim(
                (string) config(
                    'app_cache.prefix',
                    'metasoft'
                ),
                ':'
            );

        return sprintf(
            '%s:%s',
            $prefix,
            trim(
                $key,
                ':'
            )
        );
    }

    private function defaultTtl(): int
    {
        /*
         * Nunca permitir un TTL demasiado
         * pequeño accidentalmente.
         */

        return max(
            30,
            (int) config(
                'app_cache.ttl',
                600
            )
        );
    }
}