<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Shared\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_respuestas_api_incluyen_headers_de_seguridad(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Usuario activo
        |--------------------------------------------------------------------------
        */

        $user =
            $this->crearUsuario();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        /*
        |--------------------------------------------------------------------------
        | Endpoint protegido real
        |--------------------------------------------------------------------------
        */

        $response =
            $this->getJson(
                '/api/me'
            );

        $response
            ->assertOk();

        /*
        |--------------------------------------------------------------------------
        | Headers de seguridad
        |--------------------------------------------------------------------------
        */

        $this->assertTrue(
            $response->headers->has(
                'X-Content-Type-Options'
            ),
            'Falta el header X-Content-Type-Options.'
        );

        $this->assertTrue(
            $response->headers->has(
                'X-Frame-Options'
            ),
            'Falta el header X-Frame-Options.'
        );

        $this->assertTrue(
            $response->headers->has(
                'Referrer-Policy'
            ),
            'Falta el header Referrer-Policy.'
        );

        $this->assertTrue(
            $response->headers->has(
                'Permissions-Policy'
            ),
            'Falta el header Permissions-Policy.'
        );

        /*
        |--------------------------------------------------------------------------
        | Nosniff
        |--------------------------------------------------------------------------
        */

        $response
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff'
            );
    }

    private function crearUsuario(): User
    {
        $unique =
            Str::lower(
                Str::random(
                    12
                )
            );

        return User::query()
            ->create([
                'usuario' =>
                    "headers_{$unique}",

                'password' =>
                    Hash::make(
                        'Test-Security-123!'
                    ),

                'ci' =>
                    '7'
                    . random_int(
                        1000000,
                        9999999
                    ),

                'nombres' =>
                    'Usuario',

                'primer_apellido' =>
                    'Headers',

                'segundo_apellido' =>
                    'Security',

                'genero' =>
                    'Masculino',

                'fecha_nac' =>
                    '1990-01-01',

                'email' =>
                    "{$unique}@headers.test",

                'telefono' =>
                    '4455667',

                'celular' =>
                    '70000002',

                'direccion' =>
                    'Testing',

                'expedido' =>
                    'CBBA',

                'codigo_qr' =>
                    "HEADERS-{$unique}",

                'verificacion' =>
                    0,

                'foto' =>
                    null,

                'estado' =>
                    'Activo',
            ]);
    }
}