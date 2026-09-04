<?php

use App\Shared\Middleware\CheckPermission;
use App\Shared\Middleware\CheckSucursal;
use App\Shared\Middleware\CheckUserActive;
use App\Shared\Middleware\SecurityHeaders;
use App\Shared\Security\SecurityResponse;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withCommands([
        \App\Console\Commands\AsignarRolAdmin::class,
    ])

    ->withMiddleware(
        function (Middleware $middleware): void {

            /*
            |--------------------------------------------------------------------------
            | Middleware global de seguridad HTTP
            |--------------------------------------------------------------------------
            |
            | Se ejecutará en todas las respuestas del sistema.
            |
            | Agrega headers como:
            |
            | X-Content-Type-Options
            | X-Frame-Options
            | Referrer-Policy
            | Permissions-Policy
            | Strict-Transport-Security en producción HTTPS
            |
            */

            $middleware->append(
                SecurityHeaders::class
            );

            /*
            |--------------------------------------------------------------------------
            | Alias reutilizables
            |--------------------------------------------------------------------------
            |
            | Estos nombres podrán utilizarse desde cualquier módulo.
            |
            */

            $middleware->alias([
                /*
                |--------------------------------------------------------------------------
                | Usuario activo
                |--------------------------------------------------------------------------
                |
                | Debe utilizarse después de auth:sanctum.
                |
                | Ejemplo:
                |
                | 'auth:sanctum',
                | 'usuario.activo',
                |
                */

                'usuario.activo' =>
                    CheckUserActive::class,

                /*
                |--------------------------------------------------------------------------
                | Permisos RBAC
                |--------------------------------------------------------------------------
                |
                | Ejemplo:
                |
                | permiso:Modulo,Formulario,Editar
                |
                */

                'permiso' =>
                    CheckPermission::class,

                /*
                |--------------------------------------------------------------------------
                | Sucursal
                |--------------------------------------------------------------------------
                |
                | Verifica X-Sucursal-Id y que el usuario pertenezca
                | realmente a esa sucursal.
                |
                */

                'sucursal' =>
                    CheckSucursal::class,

                /*
                |--------------------------------------------------------------------------
                | Headers manuales
                |--------------------------------------------------------------------------
                |
                | Aunque SecurityHeaders ya está registrado globalmente,
                | dejamos también el alias disponible.
                |
                | Puede ser útil si posteriormente decides quitarlo
                | del middleware global.
                |
                */

                'security.headers' =>
                    SecurityHeaders::class,
                'permission' => \App\Shared\Middleware\CheckPermission::class,

            ]);
        }
    )

    ->withExceptions(
        function (Exceptions $exceptions): void {

            /*
            |--------------------------------------------------------------------------
            | Usuario no autenticado
            |--------------------------------------------------------------------------
            |
            | Estandarizamos el error para que el frontend reciba siempre:
            |
            | {
            |   "success": false,
            |   "message": "...",
            |   "code": "UNAUTHENTICATED"
            | }
            |
            */

            $exceptions->render(
                function (AuthenticationException $exception, Request $request) {
                    return SecurityResponse::unauthenticated(
                        'No autenticado.'
                    );
                }
            );
        }
    )

    ->create();