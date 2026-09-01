<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tiempo de vida por defecto
    |--------------------------------------------------------------------------
    |
    | Tiempo máximo que un dato puede permanecer almacenado en caché.
    | Se expresa en segundos.
    |
    */

    'ttl' => (int) env('APP_CACHE_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Prefijo
    |--------------------------------------------------------------------------
    |
    | Evita colisiones con otras aplicaciones que eventualmente puedan
    | utilizar el mismo sistema de caché.
    |
    */

    'prefix' => env('APP_CACHE_PREFIX', 'metasoft'),

];