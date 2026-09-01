<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Modules\Auth\Services\PermissionService;
use App\Shared\Security\SecurityConfig;
use App\Shared\Security\SecurityResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private readonly PermissionService $permissions
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string $modulo,
        string $formulario,
        string $accion,
    ): Response {
        /*
        |--------------------------------------------------------------------------
        | Usuario
        |--------------------------------------------------------------------------
        */

        $user =
            $request->user();

        if (!$user) {
            return SecurityResponse::unauthenticated();
        }

        /*
        |--------------------------------------------------------------------------
        | Normalizar parámetros
        |--------------------------------------------------------------------------
        */

        $modulo =
            trim(
                $modulo
            );

        $formulario =
            trim(
                $formulario
            );

        $accionNormalizada =
            SecurityConfig::normalizeAction(
                $accion
            );

        /*
        |--------------------------------------------------------------------------
        | Configuración inválida
        |--------------------------------------------------------------------------
        |
        | Fail closed.
        |
        | Si una ruta tiene mal configurado módulo,
        | formulario o acción, nunca permitimos continuar.
        |
        */

        if (
            $modulo === '' ||
            $formulario === '' ||
            $accionNormalizada === null
        ) {
            Log::warning(
                'Middleware de permiso mal configurado.',
                [
                    'route' =>
                        $request
                            ->route()
                            ?->getName(),

                    'path' =>
                        $request
                            ->path(),

                    'modulo' =>
                        $modulo,

                    'formulario' =>
                        $formulario,

                    'accion' =>
                        $accion,
                ]
            );

            return SecurityResponse::forbidden(
                'No tienes permiso para realizar esta acción.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Super roles
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        |
        | El Superadmin conserva acceso total como mecanismo
        | de recuperación administrativa.
        |
        | Aunque ahora permitimos modificar su matriz de permisos,
        | este bypass evita que un error de configuración deje
        | completamente bloqueado al administrador principal.
        |
        | Más adelante, cuando todos sus permisos estén correctamente
        | almacenados en BD, podemos decidir si queremos eliminar
        | este bypass y convertir al Superadmin en un rol 100 % RBAC.
        |
        */

        $superRoles =
            array_values(
                array_filter(
                    config(
                        'rbac.super_roles',
                        []
                    ),

                    static fn ($role) =>
                        is_string($role)
                        &&
                        trim($role) !== ''
                )
            );

        if (
            $superRoles !== [] &&
            $user
                ->roles()
                ->whereIn(
                    'rol',
                    $superRoles
                )
                ->where(
                    'estado',
                    'Activo'
                )
                ->exists()
        ) {
            return $next(
                $request
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Permiso RBAC normal
        |--------------------------------------------------------------------------
        */

        $allowed =
            $this
                ->permissions
                ->userHasPermission(
                    $user,
                    $modulo,
                    $formulario,
                    $accionNormalizada,
                );

        /*
        |--------------------------------------------------------------------------
        | Denegado
        |--------------------------------------------------------------------------
        */

        if (!$allowed) {
            return SecurityResponse::forbidden(
                'No tienes permiso para realizar esta acción.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Permitido
        |--------------------------------------------------------------------------
        */

        return $next(
            $request
        );
    }
}