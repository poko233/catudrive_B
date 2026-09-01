<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Shared\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Cache aislada para cada test
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
    }

    /*
    |--------------------------------------------------------------------------
    | Sin autenticación
    |--------------------------------------------------------------------------
    */

    public function test_usuario_sin_token_no_puede_acceder_a_me(): void
    {
        $response =
            $this->getJson(
                '/api/me'
            );

        $response
            ->assertUnauthorized();
    }

    /*
    |--------------------------------------------------------------------------
    | Usuario activo
    |--------------------------------------------------------------------------
    */

    public function test_usuario_activo_autenticado_puede_acceder_a_me(): void
    {
        $user =
            $this->crearUsuario(
                'Activo'
            );

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response =
            $this->getJson(
                '/api/me'
            );

        $response
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Usuario inactivo
    |--------------------------------------------------------------------------
    */

    public function test_usuario_inactivo_es_bloqueado_aunque_este_autenticado(): void
    {
        $user =
            $this->crearUsuario(
                'Inactivo'
            );

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response =
            $this->getJson(
                '/api/me'
            );

        /*
         * CheckUserActive debe impedir el acceso.
         *
         * Estamos estableciendo 403 como contrato de seguridad:
         * está autenticado, pero no está autorizado a continuar.
         */

        $response
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    private function crearUsuario(
        string $estado
    ): User {
        $unique =
            Str::lower(
                Str::random(
                    12
                )
            );

        return User::query()
            ->create([
                'usuario' =>
                    "test_{$unique}",

                'password' =>
                    Hash::make(
                        'Test-Security-123!'
                    ),

                'ci' =>
                    '9'
                    . random_int(
                        1000000,
                        9999999
                    ),

                'nombres' =>
                    'Usuario',

                'primer_apellido' =>
                    'Prueba',

                'segundo_apellido' =>
                    'Seguridad',

                'genero' =>
                    'Masculino',

                'fecha_nac' =>
                    '1990-01-01',

                'email' =>
                    "{$unique}@testing.local",

                'telefono' =>
                    '4455667',

                'celular' =>
                    '70000000',

                'direccion' =>
                    'Dirección de prueba',

                'expedido' =>
                    'CBBA',

                'codigo_qr' =>
                    "TEST-{$unique}",

                'verificacion' =>
                    0,

                'foto' =>
                    null,

                'estado' =>
                    $estado,
            ]);
    }
}