<?php

declare(strict_types=1);

namespace App\Modules\Pasaje\Services;

use App\Shared\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ChoferContextService
{
    /**
     * Cache en memoria para no repetir consultas dentro del mismo request.
     *
     * @var array<int, int|null>
     */
    private array $cache = [];

    /**
     * Devuelve el id_chofer del usuario autenticado si aplica filtrado por chofer.
     * Devuelve null si:
     *   - No hay usuario autenticado.
     *   - El usuario tiene un super rol (Administrador, Superadmin).
     *   - El usuario no tiene el rol Chofer.
     */
    public function idChoferActual(?User $user = null): ?int
    {
        $user ??= auth()->user();

        if (!$user) {
            return null;
        }

        $userId = (int) $user->id;

        if (array_key_exists($userId, $this->cache)) {
            return $this->cache[$userId];
        }

        $this->cache[$userId] = $this->resolver($user);

        return $this->cache[$userId];
    }

    public function esChofer(?User $user = null): bool
    {
        return $this->idChoferActual($user) !== null;
    }

    /**
     * Valida que un id_chofer dado pertenezca al usuario autenticado (si es chofer).
     * Si el usuario no es chofer, no valida nada (admin ve todo).
     */
    public function validarPertenece(?int $idChofer, ?User $user = null): void
    {
        $idActual = $this->idChoferActual($user);

        if ($idActual === null) {
            return;
        }

        if ($idChofer !== $idActual) {
            throw new AccessDeniedHttpException(
                'No tienes acceso a este recurso.'
            );
        }
    }

    private function resolver(User $user): ?int
    {
        $superRoles = array_values(array_filter(
            (array) config('rbac.super_roles', []),
            static fn($rol) => is_string($rol) && trim($rol) !== ''
        ));

        if ($superRoles !== [] && $user->hasAnyRole($superRoles)) {
            return null;
        }

        if ($user->hasRole('Chofer')) {
            return (int) $user->id;
        }

        return null;
    }
}