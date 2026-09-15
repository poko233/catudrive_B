<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ChangePasswordRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterUserRequest;
use App\Modules\Auth\Resources\UserProfileResource;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\PermissionService;
use App\Modules\Auth\Services\UserRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly PermissionService $permissionService,
        private readonly UserRegistrationService $registrationService,
    ) {
    }

    /**
     * POST /api/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->authService->attemptLogin(
            usuario: $data['usuario'],
            password: $data['password'],
        );

        return response()->json([
            'token' => $result['token'],
            'message' => 'Inicio de sesión exitoso.',
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            $request->user()
        );

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * GET /api/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load([
            'roles',
            'sucursales',
        ]);

        return response()->json([
            'data' => new UserProfileResource($user),
            'message' => 'Success',
        ]);
    }

    /**
     * GET /api/me/permisos
     */
    public function mePermisos(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->permissionService->getPermisos(
                $request->user()
            ),
        ]);
    }

    /**
     * PUT /api/change-password
     *
     * Cambia la contraseña del usuario autenticado y revoca
     * todas sus demás sesiones, conservando la sesión actual.
     */
    public function changePassword(
        ChangePasswordRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $this->authService->changePassword(
            user: $request->user(),
            currentPassword: $data['current_password'],
            newPassword: $data['new_password'],
        );

        return response()->json([
            'message' => 'Contraseña actualizada correctamente. Las demás sesiones fueron cerradas.',
        ]);
    }

    /**
     * POST /api/register
     *
     * Crea un usuario y le asigna roles.
     *
     * IMPORTANTE:
     *  - Endpoint restringido (auth:sanctum + usuario.activo + permiso:...).
     *  - La autorización se resuelve en middleware, no aquí.
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $user = $this->registrationService->register(
            $request->validated()
        );

        $user->load('roles');
        $user->refresh();   // ← AÑADIR: recarga para traer codigo_qr generado por el observer

        return response()->json([
            'data' => [
                'id' => $user->id,
                'usuario' => $user->usuario,
                'ci' => $user->ci,
                'nombres' => $user->nombres,
                'primer_apellido' => $user->primer_apellido,
                'segundo_apellido' => $user->segundo_apellido,
                'genero' => $user->genero,
                'fecha_nac' => $user->fecha_nac?->format('Y-m-d'),
                'email' => $user->email,
                'telefono' => $user->telefono,
                'celular' => $user->celular,
                'direccion' => $user->direccion,
                'expedido' => $user->expedido,
                'estado' => $user->estado,
                'codigo_qr' => $user->codigo_qr,
                'roles' => $user->roles->pluck('rol')->all(),
            ],
            'message' => 'Usuario registrado exitosamente',
        ], 201);
    }
}