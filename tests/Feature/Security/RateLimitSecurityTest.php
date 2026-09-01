<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Support\Str;
use Tests\TestCase;

class RateLimitSecurityTest extends TestCase
{
    public function test_login_bloquea_demasiados_intentos(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Identidad única para este test
        |--------------------------------------------------------------------------
        |
        | Así evitamos que el rate limiter pueda mezclarse
        | con ejecuciones anteriores.
        |
        */

        $usuario =
            'rate_limit_'
            . Str::lower(
                Str::random(
                    16
                )
            );

        $ip =
            '10.'
            . random_int(
                1,
                200
            )
            . '.'
            . random_int(
                1,
                200
            )
            . '.'
            . random_int(
                1,
                200
            );

        /*
        |--------------------------------------------------------------------------
        | Realizar intentos
        |--------------------------------------------------------------------------
        |
        | No fijamos aquí un número exacto del límite.
        |
        | El objetivo del test es comprobar que el limiter
        | realmente llegue a bloquear.
        |
        */

        $bloqueado =
            false;

        $ultimaRespuesta =
            null;

        for (
            $intento = 1;
            $intento <= 100;
            $intento++
        ) {
            $ultimaRespuesta =
                $this
                    ->withServerVariables([
                        'REMOTE_ADDR' =>
                            $ip,
                    ])
                    ->postJson(
                        '/api/login',
                        [
                            'usuario' =>
                                $usuario,

                            'password' =>
                                'Password-Incorrecto-123!',
                        ]
                    );

            if (
                $ultimaRespuesta->status() ===
                429
            ) {
                $bloqueado =
                    true;

                break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Verificaciones
        |--------------------------------------------------------------------------
        */

        $this->assertTrue(
            $bloqueado,
            'El endpoint /api/login no fue bloqueado por el rate limiter después de 100 intentos.'
        );

        $ultimaRespuesta
            ->assertStatus(
                429
            );
    }
}