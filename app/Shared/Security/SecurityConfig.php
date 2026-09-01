<?php

namespace App\Shared\Security;

final class SecurityConfig
{
    private function __construct()
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Acciones RBAC
    |--------------------------------------------------------------------------
    */

    public const ACTION_VER = 'Ver';

    public const ACTION_CREAR = 'Crear';

    public const ACTION_EDITAR = 'Editar';

    public const ACTION_ELIMINAR = 'Eliminar';

    /*
    |--------------------------------------------------------------------------
    | Rate limiters
    |--------------------------------------------------------------------------
    */

    public const LIMITER_LOGIN = 'login';

    public const LIMITER_API = 'api';

    public const LIMITER_WRITE = 'write';

    public const LIMITER_DELETE = 'delete';

    public const LIMITER_UPLOADS = 'uploads';

    public const LIMITER_SENSITIVE = 'sensitive';

    /*
    |--------------------------------------------------------------------------
    | Límites por defecto
    |--------------------------------------------------------------------------
    */

    public const LOGIN_PER_MINUTE = 5;

    public const LOGIN_PER_HOUR = 30;

    public const API_PER_MINUTE = 120;

    public const WRITE_PER_MINUTE = 60;

    public const DELETE_PER_MINUTE = 20;

    public const UPLOADS_PER_MINUTE = 20;

    public const SENSITIVE_PER_MINUTE = 10;

    /**
     * Acciones válidas del sistema RBAC.
     *
     * @return array<int, string>
     */
    public static function actions(): array
    {
        return [
            self::ACTION_VER,
            self::ACTION_CREAR,
            self::ACTION_EDITAR,
            self::ACTION_ELIMINAR,
        ];
    }

    /**
     * Rate limiters disponibles.
     *
     * @return array<int, string>
     */
    public static function limiters(): array
    {
        return [
            self::LIMITER_LOGIN,
            self::LIMITER_API,
            self::LIMITER_WRITE,
            self::LIMITER_DELETE,
            self::LIMITER_UPLOADS,
            self::LIMITER_SENSITIVE,
        ];
    }

    /**
     * Verifica si una acción pertenece a las acciones
     * permitidas por nuestro sistema RBAC.
     */
    public static function isValidAction(
        string $action
    ): bool {
        return self::normalizeAction($action) !== null;
    }

    /**
     * Normaliza el nombre de una acción.
     *
     * Ejemplos:
     *
     * editar  -> Editar
     * EDITAR  -> Editar
     * Editar  -> Editar
     */
    public static function normalizeAction(
        string $action
    ): ?string {
        $value = mb_strtolower(
            trim($action)
        );

        foreach (
            self::actions()
            as $allowedAction
        ) {
            if (
                mb_strtolower(
                    $allowedAction
                ) === $value
            ) {
                return $allowedAction;
            }
        }

        return null;
    }
}