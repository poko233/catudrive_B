<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Shared\Models\Rol;
use App\Shared\Models\User;
use Illuminate\Support\Facades\DB;

class UserRegistrationService
{
    /**
     * El frontend envía el género en mayúsculas, pero la columna
     * `user.genero` es un enum case-sensitive cuyos valores reales
     * son 'Masculino' / 'Femenino' / 'Otro'.
     */
    private const GENERO_MAP = [
        'MASCULINO' => 'Masculino',
        'FEMENINO' => 'Femenino',
    ];

    /**
     * Crea un usuario con sus roles asignados de forma atómica.
     *
     * @param array{
     *     usuario: string,
     *     password: string,
     *     ci: string,
     *     nombres: string,
     *     apellidoPaterno: string,
     *     apellidoMaterno?: string|null,
     *     genero: string,
     *     fecha_nac: string,
     *     email?: string|null,
     *     telefono?: string|null,
     *     celular?: string|null,
     *     direccion?: string|null,
     *     expedido?: string|null,
     *     roles: array<int, string>
     * } $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $roleIds = Rol::query()
                ->whereIn('rol', $data['roles'])
                ->pluck('id')
                ->all();

            $user = User::create([
                'usuario' => $data['usuario'],
                'password' => $data['password'], // hasheado por cast 'hashed'
                'ci' => $data['ci'],
                'nombres' => $data['nombres'],
                'primer_apellido' => $data['apellidoPaterno'],
                'segundo_apellido' => $data['apellidoMaterno'] ?? null,
                'genero' => self::GENERO_MAP[$data['genero']],
                'fecha_nac' => $data['fecha_nac'],
                'email' => $this->normalizeEmail($data['email'] ?? null),
                'telefono' => $data['telefono'] ?? null,
                'celular' => $data['celular'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'expedido' => $data['expedido'] ?? null,
                'estado' => 'Activo',
            ]);

            $user->roles()->sync($roleIds);

            return $user;
        });
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = mb_strtolower(trim($email));

        return $email === '' ? null : $email;
    }
}