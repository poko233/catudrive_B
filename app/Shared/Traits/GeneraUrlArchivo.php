<?php

declare(strict_types=1);

namespace App\Shared\Traits;

trait GeneraUrlArchivo
{
    public function urlArchivo(
        ?string $ruta
    ): ?string {
        $ruta =
            trim(
                (string) $ruta
            );

        if (
            $ruta === '' ||
            $ruta === '0' ||
            mb_strtolower($ruta) === 'null'
        ) {
            return null;
        }

        if (
            str_starts_with(
                $ruta,
                'https://'
            ) ||
            str_starts_with(
                $ruta,
                'http://'
            ) ||
            str_starts_with(
                $ruta,
                'data:image/'
            )
        ) {
            return $ruta;
        }

        /*
        |--------------------------------------------------------------------------
        | Bloquear esquemas peligrosos/desconocidos
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^[a-z][a-z0-9+.-]*:/i',
                $ruta
            ) === 1
        ) {
            return null;
        }

        $rutaLimpia =
            ltrim(
                str_replace(
                    '\\',
                    '/',
                    $ruta
                ),
                '/'
            );

        /*
        |--------------------------------------------------------------------------
        | Evitar traversal
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        |
        | ../../archivo
        |
        */

        $segmentos =
            explode(
                '/',
                $rutaLimpia
            );

        if (
            in_array(
                '..',
                $segmentos,
                true
            )
        ) {
            return null;
        }

        $base =
            rtrim(
                (string)
                config(
                    'app.url',
                    ''
                ),
                '/'
            );

        if (
            $base === ''
        ) {
            $base =
                rtrim(
                    url('/'),
                    '/'
                );
        }

        return
            $base .
            '/' .
            $rutaLimpia;
    }
}