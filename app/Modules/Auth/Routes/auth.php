<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\PasswordRecoveryController;
use App\Modules\Auth\Controllers\SidebarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::post(
    '/login',
    [
        AuthController::class,
        'login',
    ]
)
    ->middleware(
        'throttle:login'
    )
    ->name(
        'auth.login'
    );

/*
|--------------------------------------------------------------------------
| RECUPERACIÓN
|--------------------------------------------------------------------------
*/

Route::post(
    '/forgot-password',
    [
        PasswordRecoveryController::class,
        'forgotPassword',
    ]
)
    ->middleware(
        'throttle:3,1'
    )
    ->name(
        'auth.forgot-password'
    );

/*
|--------------------------------------------------------------------------
| VALIDAR CÓDIGO
|--------------------------------------------------------------------------
*/

Route::post(
    '/verify-reset-code',
    [
        PasswordRecoveryController::class,
        'verifyResetCode',
    ]
)
    ->middleware(
        'throttle:5,1'
    )
    ->name(
        'auth.verify-reset-code'
    );

/*
|--------------------------------------------------------------------------
| CAMBIAR PASSWORD
|--------------------------------------------------------------------------
*/

Route::post(
    '/reset-password',
    [
        PasswordRecoveryController::class,
        'resetPassword',
    ]
)
    ->middleware(
        'throttle:5,1'
    )
    ->name(
        'auth.reset-password'
    );

/*
|--------------------------------------------------------------------------
| AUTENTICADO
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'throttle:api',
])->group(
    function (): void {
        Route::post(
            '/logout',
            [
                AuthController::class,
                'logout',
            ]
        )
            ->middleware(
                'throttle:write'
            )
            ->name(
                'auth.logout'
            );

        Route::middleware(
            'usuario.activo'
        )->group(
            function (): void {
                Route::get(
                    '/me',
                    [
                        AuthController::class,
                        'me',
                    ]
                )
                    ->name(
                        'auth.me'
                    );

                Route::get(
                    '/me/permisos',
                    [
                        AuthController::class,
                        'mePermisos',
                    ]
                )
                    ->name(
                        'auth.permissions'
                    );

                Route::get(
                    '/sidebar',
                    [
                        SidebarController::class,
                        'index',
                    ]
                )
                    ->name(
                        'auth.sidebar'
                    );

                Route::put(
                    '/change-password',
                    [
                        AuthController::class,
                        'changePassword',
                    ]
                )
                    ->middleware(
                        'throttle:sensitive'
                    )
                    ->name(
                        'auth.change-password'
                    );
            }
        );
    }
);