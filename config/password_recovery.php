<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tiempo de vida del código
    |--------------------------------------------------------------------------
    |
    | Minutos durante los cuales el código es válido.
    |
    */

    'expire_minutes' =>
        max(
            5,
            (int) env(
                'PASSWORD_RESET_EXPIRE_MINUTES',
                10
            )
        ),

    /*
    |--------------------------------------------------------------------------
    | Tiempo mínimo entre correos
    |--------------------------------------------------------------------------
    |
    | Evita enviar múltiples correos al mismo usuario
    | en pocos segundos.
    |
    */

    'resend_seconds' =>
        max(
            30,
            (int) env(
                'PASSWORD_RESET_RESEND_SECONDS',
                60
            )
        ),
];