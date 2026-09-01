<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Shared\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Autentica al usuario y genera un token Sanctum.
     */
    public function attemptLogin(
        string $usuario,
        string $password
    ): array {
        $usuario =
            trim(
                $usuario
            );

        $user =
            User::query()
                ->where(
                    'usuario',
                    $usuario
                )
                ->first();

        if (
            !$user
            ||
            !Hash::check(
                $password,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'usuario' => [
                    'Las credenciales no son correctas.',
                ],
            ]);
        }

        $estado =
            mb_strtoupper(
                trim(
                    (string)
                    $user->estado
                )
            );

        if (
            $estado !== 'ACTIVO'
        ) {
            throw ValidationException::withMessages([
                'usuario' => [
                    'El usuario está inactivo.',
                ],
            ]);
        }

        /*
         * Nunca cachear este token.
         */

        $token =
            $user
                ->createToken(
                    'api-token'
                )
                ->plainTextToken;

        return [
            'token' =>
                $token,
        ];
    }

    /**
     * Elimina únicamente el token utilizado
     * en la sesión actual.
     */
    public function logout(
        User $user
    ): void {
        $user
            ->currentAccessToken()
            ?->delete();
    }
}