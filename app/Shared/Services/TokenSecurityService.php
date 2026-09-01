<?php

declare(strict_types=1);

namespace App\Shared\Services;

use App\Shared\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class TokenSecurityService
{
    public function revokeCurrentToken(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    public function revokeOtherTokens(User $user): void
    {
        $currentToken = $user->currentAccessToken();

        if (!$currentToken instanceof PersonalAccessToken) {
            $this->revokeAllTokens($user);
            return;
        }

        $user->tokens()
            ->whereKeyNot($currentToken->getKey())
            ->delete();
    }

    public function revokeAllTokensByUserId(int $userId): void
    {
        $user = User::query()->find($userId);

        if (!$user) {
            return;
        }

        $this->revokeAllTokens($user);
    }

    public function revokeIfInactive(User $user): bool
    {
        $estado = mb_strtoupper(
            trim((string) $user->estado)
        );

        if ($estado === 'ACTIVO') {
            return false;
        }

        $this->revokeAllTokens($user);

        return true;
    }
}