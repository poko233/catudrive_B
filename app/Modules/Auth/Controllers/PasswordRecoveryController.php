<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Requests\VerifyResetCodeRequest;
use App\Modules\Auth\Services\PasswordRecoveryService;
use Illuminate\Http\JsonResponse;

class PasswordRecoveryController extends Controller
{
    public function __construct(
        private readonly PasswordRecoveryService $service
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | SOLICITAR CÓDIGO
    |--------------------------------------------------------------------------
    */

    public function forgotPassword(
        ForgotPasswordRequest $request
    ): JsonResponse {
        $data =
            $request->validated();

        $this
            ->service
            ->requestResetCode(
                $data['email']
            );

        return response()->json([
            'message' =>
                'Si el correo está registrado y la cuenta está activa, recibirás un código para restablecer tu contraseña.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR CÓDIGO
    |--------------------------------------------------------------------------
    */

    public function verifyResetCode(
        VerifyResetCodeRequest $request
    ): JsonResponse {
        $data =
            $request->validated();

        $this
            ->service
            ->verifyResetCode(
                email:
                    $data['email'],

                code:
                    $data['code'],
            );

        return response()->json([
            'message' =>
                'Código verificado correctamente.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RESTABLECER PASSWORD
    |--------------------------------------------------------------------------
    */

    public function resetPassword(
        ResetPasswordRequest $request
    ): JsonResponse {
        $data =
            $request->validated();

        $this
            ->service
            ->resetPassword(
                email:
                    $data['email'],

                code:
                    $data['code'],

                newPassword:
                    $data['new_password'],
            );

        return response()->json([
            'message' =>
                'Contraseña restablecida correctamente. Ya puedes iniciar sesión.',
        ]);
    }
}