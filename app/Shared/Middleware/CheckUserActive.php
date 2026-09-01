<?php

namespace App\Shared\Middleware;

use App\Shared\Security\SecurityResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return SecurityResponse::unauthenticated();
        }

        $estado = mb_strtoupper(
            trim((string) $user->estado)
        );

        if ($estado !== 'ACTIVO') {
            return SecurityResponse::inactiveUser(
                'Tu usuario se encuentra inactivo. Contacta con un administrador.'
            );
        }

        return $next($request);
    }
}