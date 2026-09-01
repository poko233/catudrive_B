<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Shared\Services\AppCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheSecurityTest extends TestCase
{
    private AppCacheService $cache;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Store aislado
        |--------------------------------------------------------------------------
        */

        config()->set(
            'cache.default',
            'array'
        );

        config()->set(
            'app_cache.prefix',
            'metasoft_test'
        );

        config()->set(
            'app_cache.ttl',
            60
        );

        Cache::clear();

        $this->cache =
            app(
                AppCacheService::class
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Cache hit
    |--------------------------------------------------------------------------
    */

    public function test_remember_no_ejecuta_dos_veces_la_consulta_si_hay_cache(): void
    {
        $ejecuciones =
            0;

        $primero =
            $this->cache
                ->remember(
                    'security:catalogo',
                    function () use (
                        &$ejecuciones
                    ): array {
                        $ejecuciones++;

                        return [
                            'version' =>
                                1,
                        ];
                    }
                );

        $segundo =
            $this->cache
                ->remember(
                    'security:catalogo',
                    function () use (
                        &$ejecuciones
                    ): array {
                        $ejecuciones++;

                        return [
                            'version' =>
                                2,
                        ];
                    }
                );

        /*
         * El segundo callback NO debe ejecutarse.
         */

        $this->assertSame(
            1,
            $ejecuciones
        );

        $this->assertSame(
            $primero,
            $segundo
        );

        $this->assertSame(
            1,
            $segundo[
                'version'
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidación
    |--------------------------------------------------------------------------
    */

    public function test_forget_obliga_a_recargar_el_dato(): void
    {
        $ejecuciones =
            0;

        $this->cache
            ->remember(
                'security:invalidacion',
                function () use (
                    &$ejecuciones
                ): int {
                    return ++$ejecuciones;
                }
            );

        /*
         * Segunda lectura desde caché.
         */

        $antes =
            $this->cache
                ->remember(
                    'security:invalidacion',
                    function () use (
                        &$ejecuciones
                    ): int {
                        return ++$ejecuciones;
                    }
                );

        $this->assertSame(
            1,
            $antes
        );

        $this->assertSame(
            1,
            $ejecuciones
        );

        /*
         * Invalidar.
         */

        $this->cache
            ->forget(
                'security:invalidacion'
            );

        /*
         * Debe volver a ejecutar el callback.
         */

        $despues =
            $this->cache
                ->remember(
                    'security:invalidacion',
                    function () use (
                        &$ejecuciones
                    ): int {
                        return ++$ejecuciones;
                    }
                );

        $this->assertSame(
            2,
            $despues
        );

        $this->assertSame(
            2,
            $ejecuciones
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Aislamiento por usuario
    |--------------------------------------------------------------------------
    */

    public function test_cache_de_un_usuario_no_se_mezcla_con_otro_usuario(): void
    {
        $usuarioUno =
            $this->cache
                ->rememberForUser(
                    1001,
                    'permisos',
                    fn (): array => [
                        'usuario' =>
                            1001,

                        'roles' => [
                            'Administrador',
                        ],
                    ]
                );

        $usuarioDos =
            $this->cache
                ->rememberForUser(
                    2002,
                    'permisos',
                    fn (): array => [
                        'usuario' =>
                            2002,

                        'roles' => [
                            'Operador',
                        ],
                    ]
                );

        $this->assertSame(
            1001,
            $usuarioUno[
                'usuario'
            ]
        );

        $this->assertSame(
            2002,
            $usuarioDos[
                'usuario'
            ]
        );

        $this->assertNotSame(
            $usuarioUno,
            $usuarioDos
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Invalidación aislada por usuario
    |--------------------------------------------------------------------------
    */

    public function test_invalidar_un_usuario_no_elimina_cache_de_otro_usuario(): void
    {
        $ejecucionesUsuarioUno =
            0;

        $ejecucionesUsuarioDos =
            0;

        $this->cache
            ->rememberForUser(
                1001,
                'sucursales',
                function () use (
                    &$ejecucionesUsuarioUno
                ): array {
                    $ejecucionesUsuarioUno++;

                    return [
                        'Sucursal A',
                    ];
                }
            );

        $this->cache
            ->rememberForUser(
                2002,
                'sucursales',
                function () use (
                    &$ejecucionesUsuarioDos
                ): array {
                    $ejecucionesUsuarioDos++;

                    return [
                        'Sucursal B',
                    ];
                }
            );

        /*
         * Invalidamos únicamente usuario 1001.
         */

        $this->cache
            ->forgetForUser(
                1001,
                'sucursales'
            );

        /*
         * Usuario 1001 debe reconstruirse.
         */

        $this->cache
            ->rememberForUser(
                1001,
                'sucursales',
                function () use (
                    &$ejecucionesUsuarioUno
                ): array {
                    $ejecucionesUsuarioUno++;

                    return [
                        'Sucursal A actualizada',
                    ];
                }
            );

        /*
         * Usuario 2002 debe continuar usando su caché anterior.
         */

        $usuarioDos =
            $this->cache
                ->rememberForUser(
                    2002,
                    'sucursales',
                    function () use (
                        &$ejecucionesUsuarioDos
                    ): array {
                        $ejecucionesUsuarioDos++;

                        return [
                            'ESTO NO DEBERÍA EJECUTARSE',
                        ];
                    }
                );

        $this->assertSame(
            2,
            $ejecucionesUsuarioUno
        );

        $this->assertSame(
            1,
            $ejecucionesUsuarioDos
        );

        $this->assertSame(
            [
                'Sucursal B',
            ],
            $usuarioDos
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Aislamiento por rol
    |--------------------------------------------------------------------------
    */

    public function test_cache_de_roles_tambien_esta_separada(): void
    {
        $rolUno =
            $this->cache
                ->rememberForRole(
                    10,
                    'sidebar',
                    fn (): array => [
                        'rol' =>
                            10,
                    ]
                );

        $rolDos =
            $this->cache
                ->rememberForRole(
                    20,
                    'sidebar',
                    fn (): array => [
                        'rol' =>
                            20,
                    ]
                );

        $this->assertSame(
            10,
            $rolUno[
                'rol'
            ]
        );

        $this->assertSame(
            20,
            $rolDos[
                'rol'
            ]
        );

        $this->assertNotSame(
            $rolUno,
            $rolDos
        );
    }
}