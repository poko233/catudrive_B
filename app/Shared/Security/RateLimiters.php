<?php

namespace App\Shared\Security;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class RateLimiters
{
    private function __construct()
    {
    }

    /**
     * Registra todos los rate limiters
     * reutilizables del sistema.
     */
    public static function register(): void
    {
        self::registerLoginLimiter();

        self::registerApiLimiter();

        self::registerWriteLimiter();

        self::registerDeleteLimiter();

        self::registerUploadsLimiter();

        self::registerSensitiveLimiter();
    }

    /**
     * LOGIN
     *
     * Protección contra fuerza bruta.
     *
     * Límites:
     *
     * 5 intentos por minuto.
     * 30 intentos por hora.
     *
     * Se identifica mediante:
     *
     * usuario/email + IP
     */
    private static function registerLoginLimiter(): void
    {
        RateLimiter::for(
            SecurityConfig::LIMITER_LOGIN,

            function (
                Request $request
            ): array {
                $key =
                    self::loginKey(
                        $request
                    );

                return [
                    Limit::perMinute(
                        SecurityConfig::LOGIN_PER_MINUTE
                    )
                        ->by(
                            "login:minute:{$key}"
                        )
                        ->response(
                            fn (
                                Request $request,
                                array $headers
                            ) =>
                            SecurityResponse::tooManyRequests(
                                'Demasiados intentos de inicio de sesión. Espera un momento antes de volver a intentar.',
                                $headers,
                            )
                        ),

                    Limit::perHour(
                        SecurityConfig::LOGIN_PER_HOUR
                    )
                        ->by(
                            "login:hour:{$key}"
                        )
                        ->response(
                            fn (
                                Request $request,
                                array $headers
                            ) =>
                            SecurityResponse::tooManyRequests(
                                'Se alcanzó el límite de intentos de inicio de sesión. Intenta nuevamente más tarde.',
                                $headers,
                            )
                        ),
                ];
            },
        );
    }

    /**
     * API GENERAL
     *
     * Para consultas normales.
     *
     * Ejemplo:
     *
     * GET usuarios
     * GET módulos
     * GET formularios
     * GET roles
     *
     * 120 peticiones por minuto.
     */
    private static function registerApiLimiter(): void
    {
        RateLimiter::for(
            SecurityConfig::LIMITER_API,

            fn (
                Request $request
            ) =>
            Limit::perMinute(
                SecurityConfig::API_PER_MINUTE
            )
                ->by(
                    'api:' .
                    self::userOrIpKey(
                        $request
                    )
                )
                ->response(
                    fn (
                        Request $request,
                        array $headers
                    ) =>
                    SecurityResponse::tooManyRequests(
                        'Has realizado demasiadas solicitudes a la API. Intenta nuevamente en unos momentos.',
                        $headers,
                    )
                ),
        );
    }

    /**
     * ESCRITURA
     *
     * Para:
     *
     * POST
     * PUT
     * PATCH
     *
     * normales.
     *
     * 60 operaciones por minuto.
     */
    private static function registerWriteLimiter(): void
    {
        RateLimiter::for(
            SecurityConfig::LIMITER_WRITE,

            fn (
                Request $request
            ) =>
            Limit::perMinute(
                SecurityConfig::WRITE_PER_MINUTE
            )
                ->by(
                    'write:' .
                    self::userOrIpKey(
                        $request
                    )
                )
                ->response(
                    fn (
                        Request $request,
                        array $headers
                    ) =>
                    SecurityResponse::tooManyRequests(
                        'Has realizado demasiadas operaciones de escritura. Intenta nuevamente en unos momentos.',
                        $headers,
                    )
                ),
        );
    }

    /**
     * DELETE
     *
     * Operaciones destructivas.
     *
     * Se utiliza un límite más bajo.
     *
     * 20 eliminaciones por minuto.
     */
    private static function registerDeleteLimiter(): void
    {
        RateLimiter::for(
            SecurityConfig::LIMITER_DELETE,

            fn (
                Request $request
            ) =>
            Limit::perMinute(
                SecurityConfig::DELETE_PER_MINUTE
            )
                ->by(
                    'delete:' .
                    self::userOrIpKey(
                        $request
                    )
                )
                ->response(
                    fn (
                        Request $request,
                        array $headers
                    ) =>
                    SecurityResponse::tooManyRequests(
                        'Has realizado demasiadas eliminaciones. Espera antes de volver a intentar.',
                        $headers,
                    )
                ),
        );
    }

    /**
     * UPLOADS
     *
     * Para:
     *
     * fotografías
     * documentos
     * imágenes
     * archivos
     *
     * 20 cargas por minuto.
     */
    private static function registerUploadsLimiter(): void
    {
        RateLimiter::for(
            SecurityConfig::LIMITER_UPLOADS,

            fn (
                Request $request
            ) =>
            Limit::perMinute(
                SecurityConfig::UPLOADS_PER_MINUTE
            )
                ->by(
                    'uploads:' .
                    self::userOrIpKey(
                        $request
                    )
                )
                ->response(
                    fn (
                        Request $request,
                        array $headers
                    ) =>
                    SecurityResponse::tooManyRequests(
                        'Has realizado demasiadas cargas de archivos. Espera antes de volver a intentar.',
                        $headers,
                    )
                ),
        );
    }

    /**
     * OPERACIONES SENSIBLES
     *
     * Ejemplos:
     *
     * cambiar contraseña
     * modificar permisos
     * modificar roles
     * asignar administradores
     * operaciones críticas
     *
     * 10 operaciones por minuto.
     */
    private static function registerSensitiveLimiter(): void
    {
        RateLimiter::for(
            SecurityConfig::LIMITER_SENSITIVE,

            fn (
                Request $request
            ) =>
            Limit::perMinute(
                SecurityConfig::SENSITIVE_PER_MINUTE
            )
                ->by(
                    'sensitive:' .
                    self::userOrIpKey(
                        $request
                    )
                )
                ->response(
                    fn (
                        Request $request,
                        array $headers
                    ) =>
                    SecurityResponse::tooManyRequests(
                        'Se alcanzó el límite temporal para esta operación sensible. Intenta nuevamente en unos momentos.',
                        $headers,
                    )
                ),
        );
    }

    /**
     * Genera la llave del rate limiter.
     *
     * Usuario autenticado:
     *
     * user:15
     *
     * Usuario no autenticado:
     *
     * ip:<hash>
     *
     * No almacenamos directamente la IP.
     */
    private static function userOrIpKey(
        Request $request
    ): string {
        $userId =
            $request
                ->user()
                ?->getAuthIdentifier();

        if (
            $userId !== null
        ) {
            return
                'user:' .
                $userId;
        }

        return
            'ip:' .
            hash(
                'sha256',
                (string)
                $request->ip()
            );
    }

    /**
     * Genera una llave especial
     * para intentos de login.
     *
     * Utiliza:
     *
     * usuario/email + IP
     *
     * pero se guarda como SHA-256.
     *
     * Así evitamos guardar:
     *
     * cristian@correo.com
     * 192.168.1.20
     *
     * directamente en la caché.
     */
    private static function loginKey(
        Request $request
    ): string {
        $identity =
            trim(
                (string) (
                    $request->input(
                        'usuario'
                    )
                    ??
                    $request->input(
                        'email'
                    )
                    ??
                    ''
                )
            );

        $identity =
            mb_strtolower(
                $identity
            );

        $ip =
            (string)
            $request->ip();

        return hash(
            'sha256',
            $identity .
            '|' .
            $ip
        );
    }
}