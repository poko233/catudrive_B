<?php

namespace App\Shared\Middleware;

use App\Shared\Security\SecurityResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSucursal
{
    private const HEADER =
        'X-Sucursal-Id';

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user =
            $request->user();

        if (!$user) {
            return SecurityResponse::unauthenticated();
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener sucursal
        |--------------------------------------------------------------------------
        */

        $rawSucursalId =
            $request->header(
                self::HEADER
            );

        if (
            $rawSucursalId === null ||
            trim(
                (string) $rawSucursalId
            ) === ''
        ) {
            return SecurityResponse::badRequest(
                'El header X-Sucursal-Id es obligatorio.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validar ID
        |--------------------------------------------------------------------------
        */

        $idSucursal =
            filter_var(
                $rawSucursalId,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                    ],
                ]
            );

        if (
            $idSucursal === false
        ) {
            return SecurityResponse::badRequest(
                'El header X-Sucursal-Id debe contener un identificador de sucursal válido.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Comprobar acceso
        |--------------------------------------------------------------------------
        */

        $perteneceASucursal =
            $user
                ->sucursales()
                ->where(
                    'sucursal.id',
                    (int) $idSucursal
                )
                ->exists();

        if (
            !$perteneceASucursal
        ) {
            return SecurityResponse::forbidden(
                'No tienes acceso a esta sucursal.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Guardar sucursal resuelta
        |--------------------------------------------------------------------------
        |
        | Controllers, Services u otros middlewares pueden recuperarla:
        |
        | $request->attributes->get('id_sucursal');
        |
        */

        $request
            ->attributes
            ->set(
                'id_sucursal',
                (int) $idSucursal
            );

        return $next(
            $request
        );
    }
}