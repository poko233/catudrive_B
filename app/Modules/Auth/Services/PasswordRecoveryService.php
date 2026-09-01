<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Mail\ResetPasswordMail;
use App\Shared\Models\PasswordResetCode;
use App\Shared\Models\User;
use App\Shared\Services\AuditService;
use App\Shared\Services\TokenSecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordRecoveryService
{
    public function __construct(
        private readonly TokenSecurityService $tokenSecurity,
        private readonly AuditService $audit,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | SOLICITAR CÓDIGO
    |--------------------------------------------------------------------------
    */

    public function requestResetCode(
        string $email
    ): void {
        $correo =
            $this->normalizeEmail(
                $email
            );

        $users =
            User::query()
                ->whereRaw(
                    'LOWER(email) = ?',
                    [$correo]
                )
                ->limit(2)
                ->get();

        /*
         * Respuesta silenciosa para evitar
         * enumeración de usuarios.
         */

        if ($users->count() !== 1) {
            return;
        }

        /** @var User $user */
        $user =
            $users->first();

        if (!$user->isActive()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | COOLDOWN
        |--------------------------------------------------------------------------
        */

        $ultimoCodigo =
            PasswordResetCode::query()
                ->where(
                    'correo',
                    $correo
                )
                ->where(
                    'used',
                    false
                )
                ->orderByDesc('id')
                ->first();

        if (
            $ultimoCodigo &&
            $ultimoCodigo->created_at &&
            $ultimoCodigo
                ->created_at
                ->greaterThan(
                    now()->subSeconds(
                        $this->resendSeconds()
                    )
                )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CÓDIGO
        |--------------------------------------------------------------------------
        */

        $code =
            str_pad(
                (string) random_int(
                    0,
                    999999
                ),
                6,
                '0',
                STR_PAD_LEFT
            );

        /*
        |--------------------------------------------------------------------------
        | GUARDAR
        |--------------------------------------------------------------------------
        */

        $resetCode =
            DB::transaction(
                function () use (
                    $correo,
                    $code
                ): PasswordResetCode {
                    /*
                     * Invalidamos códigos anteriores.
                     */

                    PasswordResetCode::query()
                        ->where(
                            'correo',
                            $correo
                        )
                        ->where(
                            'used',
                            false
                        )
                        ->update([
                            'used' => true,
                            'updated_at' => now(),
                        ]);

                    return PasswordResetCode::query()
                        ->create([
                            'correo' =>
                                $correo,

                            'code' =>
                                $code,

                            'expires_at' =>
                                now()->addMinutes(
                                    $this->expireMinutes()
                                ),

                            'used' =>
                                false,
                        ]);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | NOMBRE
        |--------------------------------------------------------------------------
        */

        $userName =
            trim(
                implode(
                    ' ',
                    array_filter([
                        $user->nombres,
                        $user->primer_apellido,
                    ])
                )
            );

        if ($userName === '') {
            $userName =
                (string) $user->usuario;
        }

        /*
        |--------------------------------------------------------------------------
        | SMTP
        |--------------------------------------------------------------------------
        */

        try {
            Mail::to(
                $correo
            )->send(
                new ResetPasswordMail(
                    code:
                        $code,

                    userName:
                        $userName,

                    expireMinutes:
                        $this->expireMinutes(),
                )
            );
        } catch (
            Throwable $exception
        ) {
            PasswordResetCode::query()
                ->whereKey(
                    $resetCode->id
                )
                ->update([
                    'used' => true,
                    'updated_at' => now(),
                ]);

            Log::error(
                'Error enviando correo de recuperación de contraseña.',
                [
                    'user_id' =>
                        (int) $user->id,

                    'exception' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR CÓDIGO - FASE 1
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    |
    | Aquí NO marcamos el código como usado.
    |
    | Solamente comprobamos que:
    |
    | - exista;
    | - no esté usado;
    | - no haya expirado;
    | - sea correcto;
    | - corresponda a un usuario activo.
    |
    */

    public function verifyResetCode(
        string $email,
        string $code
    ): void {
        $correo =
            $this->normalizeEmail(
                $email
            );

        $code =
            trim(
                $code
            );

        /** @var PasswordResetCode|null $resetCode */
        $resetCode =
            PasswordResetCode::query()
                ->where(
                    'correo',
                    $correo
                )
                ->where(
                    'used',
                    false
                )
                ->orderByDesc('id')
                ->first();

        $this->validateCodeRecord(
            resetCode:
                $resetCode,

            code:
                $code,
        );

        /*
        |--------------------------------------------------------------------------
        | USUARIO
        |--------------------------------------------------------------------------
        */

        $user =
            $this->findUniqueActiveUser(
                $correo
            );

        if (!$user) {
            $this->throwInvalidCode();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RESTABLECER CONTRASEÑA - FASE 2
    |--------------------------------------------------------------------------
    */

    public function resetPassword(
        string $email,
        string $code,
        string $newPassword
    ): void {
        $correo =
            $this->normalizeEmail(
                $email
            );

        $code =
            trim(
                $code
            );

        /*
         * La comprobación se repite dentro
         * de una transacción.
         *
         * El frontend NO es una fuente confiable.
         */

        $user =
            DB::transaction(
                function () use (
                    $correo,
                    $code,
                    $newPassword
                ): User {
                    /*
                    |--------------------------------------------------------------------------
                    | BLOQUEAR CÓDIGO
                    |--------------------------------------------------------------------------
                    */

                    /** @var PasswordResetCode|null $resetCode */
                    $resetCode =
                        PasswordResetCode::query()
                            ->where(
                                'correo',
                                $correo
                            )
                            ->where(
                                'used',
                                false
                            )
                            ->orderByDesc('id')
                            ->lockForUpdate()
                            ->first();

                    /*
                     * Volvemos a validar el código.
                     */

                    $this->validateCodeRecord(
                        resetCode:
                            $resetCode,

                        code:
                            $code,
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | USUARIO
                    |--------------------------------------------------------------------------
                    */

                    $users =
                        User::query()
                            ->whereRaw(
                                'LOWER(email) = ?',
                                [$correo]
                            )
                            ->limit(2)
                            ->lockForUpdate()
                            ->get();

                    if ($users->count() !== 1) {
                        $this->throwInvalidCode();
                    }

                    /** @var User $user */
                    $user =
                        $users->first();

                    if (!$user->isActive()) {
                        $this->throwInvalidCode();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CONTRASEÑA DIFERENTE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        Hash::check(
                            $newPassword,
                            (string) $user->password
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'new_password' => [
                                'La nueva contraseña no puede ser igual a la contraseña actual.',
                            ],
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ACTUALIZAR PASSWORD
                    |--------------------------------------------------------------------------
                    */

                    $user
                        ->forceFill([
                            'password' =>
                                Hash::make(
                                    $newPassword
                                ),
                        ])
                        ->save();

                    /*
                    |--------------------------------------------------------------------------
                    | CONSUMIR CÓDIGO
                    |--------------------------------------------------------------------------
                    */

                    $resetCode
                        ->forceFill([
                            'used' =>
                                true,
                        ])
                        ->save();

                    /*
                     * Por seguridad invalidamos cualquier
                     * otro código pendiente del correo.
                     */

                    PasswordResetCode::query()
                        ->where(
                            'correo',
                            $correo
                        )
                        ->where(
                            'used',
                            false
                        )
                        ->update([
                            'used' =>
                                true,

                            'updated_at' =>
                                now(),
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | CERRAR SESIONES
                    |--------------------------------------------------------------------------
                    */

                    $this
                        ->tokenSecurity
                        ->revokeAllTokens(
                            $user
                        );

                    return $user;
                }
            );

        /*
        |--------------------------------------------------------------------------
        | AUDITORÍA
        |--------------------------------------------------------------------------
        */

        $this
            ->audit
            ->log(
                action:
                    'Editar',

                resource:
                    'PasswordUsuario',

                resourceId:
                    (int) $user->id,

                context: [
                    'motivo' =>
                        'recuperacion_password',
                ],
            );
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR REGISTRO DEL CÓDIGO
    |--------------------------------------------------------------------------
    */

    private function validateCodeRecord(
        ?PasswordResetCode $resetCode,
        string $code
    ): void {
        if (!$resetCode) {
            $this->throwInvalidCode();
        }

        /*
        |--------------------------------------------------------------------------
        | EXPIRACIÓN
        |--------------------------------------------------------------------------
        */

        if (
            !$resetCode->expires_at ||
            now()->greaterThan(
                $resetCode->expires_at
            )
        ) {
            $this->throwExpiredCode();
        }

        /*
        |--------------------------------------------------------------------------
        | CÓDIGO
        |--------------------------------------------------------------------------
        */

        if (
            !hash_equals(
                (string) $resetCode->code,
                $code
            )
        ) {
            $this->throwInvalidCode();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | USUARIO ÚNICO Y ACTIVO
    |--------------------------------------------------------------------------
    */

    private function findUniqueActiveUser(
        string $correo
    ): ?User {
        $users =
            User::query()
                ->whereRaw(
                    'LOWER(email) = ?',
                    [$correo]
                )
                ->limit(2)
                ->get();

        if ($users->count() !== 1) {
            return null;
        }

        /** @var User $user */
        $user =
            $users->first();

        if (!$user->isActive()) {
            return null;
        }

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | ERROR CÓDIGO
    |--------------------------------------------------------------------------
    */

    private function throwInvalidCode(): never
    {
        throw ValidationException::withMessages([
            'code' => [
                'El código de recuperación no es válido o ya fue utilizado.',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ERROR EXPIRACIÓN
    |--------------------------------------------------------------------------
    */

    private function throwExpiredCode(): never
    {
        throw ValidationException::withMessages([
            'code' => [
                'El código de recuperación ha expirado. Solicita uno nuevo.',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR EMAIL
    |--------------------------------------------------------------------------
    */

    private function normalizeEmail(
        string $email
    ): string {
        return mb_strtolower(
            trim(
                $email
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EXPIRACIÓN
    |--------------------------------------------------------------------------
    */

    private function expireMinutes(): int
    {
        return max(
            5,
            (int) config(
                'password_recovery.expire_minutes',
                10
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REENVÍO
    |--------------------------------------------------------------------------
    */

    private function resendSeconds(): int
    {
        return max(
            30,
            (int) config(
                'password_recovery.resend_seconds',
                60
            )
        );
    }
}