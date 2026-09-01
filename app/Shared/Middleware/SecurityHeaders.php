<?php

namespace App\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        /** @var Response $response */
        $response =
            $next($request);

        /*
        |--------------------------------------------------------------------------
        | Evitar MIME sniffing
        |--------------------------------------------------------------------------
        */

        $response
            ->headers
            ->set(
                'X-Content-Type-Options',
                'nosniff'
            );

        /*
        |--------------------------------------------------------------------------
        | Evitar iframe
        |--------------------------------------------------------------------------
        */

        $response
            ->headers
            ->set(
                'X-Frame-Options',
                'DENY'
            );

        /*
        |--------------------------------------------------------------------------
        | No enviar Referer
        |--------------------------------------------------------------------------
        */

        $response
            ->headers
            ->set(
                'Referrer-Policy',
                'no-referrer'
            );

        /*
        |--------------------------------------------------------------------------
        | APIs del navegador
        |--------------------------------------------------------------------------
        */

        $response
            ->headers
            ->set(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=()'
            );

        /*
        |--------------------------------------------------------------------------
        | Adobe cross-domain
        |--------------------------------------------------------------------------
        */

        $response
            ->headers
            ->set(
                'X-Permitted-Cross-Domain-Policies',
                'none'
            );

        /*
        |--------------------------------------------------------------------------
        | HSTS
        |--------------------------------------------------------------------------
        |
        | Solo se activa:
        |
        | - en production
        | - cuando la petición utiliza HTTPS
        |
        | Nunca durante desarrollo HTTP local.
        |
        */

        if (
            app()->environment(
                'production'
            ) &&
            $request->isSecure()
        ) {
            $response
                ->headers
                ->set(
                    'Strict-Transport-Security',
                    'max-age=31536000; includeSubDomains'
                );
        }

        return $response;
    }
}