<?php

namespace App\Shared\Security;

use Illuminate\Http\JsonResponse;

final class SecurityResponse
{
    private function __construct()
    {
    }

    /**
     * 401
     *
     * Usuario no autenticado.
     *
     * @param array<string, string> $headers
     */
    public static function unauthenticated(
        string $message = 'No autenticado.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 401,
            code: 'UNAUTHENTICATED',
            headers: $headers,
        );
    }

    /**
     * 403
     *
     * Usuario autenticado pero sin autorización.
     *
     * @param array<string, string> $headers
     */
    public static function forbidden(
        string $message =
            'No tienes permiso para realizar esta acción.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 403,
            code: 'FORBIDDEN',
            headers: $headers,
        );
    }

    /**
     * 403 específico para usuario inactivo.
     *
     * @param array<string, string> $headers
     */
    public static function inactiveUser(
        string $message =
            'Tu usuario se encuentra inactivo.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 403,
            code: 'USER_INACTIVE',
            headers: $headers,
        );
    }

    /**
     * 429
     *
     * Demasiadas solicitudes.
     *
     * Laravel enviará normalmente headers como:
     *
     * Retry-After
     * X-RateLimit-Limit
     * X-RateLimit-Remaining
     *
     * @param array<string, string|int> $headers
     */
    public static function tooManyRequests(
        string $message =
            'Has realizado demasiadas solicitudes. Intenta nuevamente en unos momentos.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 429,
            code: 'RATE_LIMIT_EXCEEDED',
            headers: $headers,
        );
    }

    /**
     * 400
     *
     * Solicitud incorrecta relacionada con
     * seguridad o configuración.
     *
     * @param array<string, string> $headers
     */
    public static function badRequest(
        string $message =
            'La solicitud no es válida.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 400,
            code: 'BAD_REQUEST',
            headers: $headers,
        );
    }

    /**
     * Constructor central de respuestas
     * relacionadas con seguridad.
     *
     * @param array<string, string|int> $headers
     */
    public static function error(
        string $message,
        int $status,
        string $code,
        array $headers = [],
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,

                'message' => $message,

                'code' => $code,
            ],
            $status,
            $headers,
        );
    }
}