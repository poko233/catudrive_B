<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Modules\Auth\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'user';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'usuario',
        'password',
        'ci',
        'nombres',
        'primer_apellido',
        'segundo_apellido',
        'genero',
        'fecha_nac',
        'email',
        'telefono',
        'celular',
        'direccion',
        'expedido',
        'codigo_qr',
        'verificacion',
        'foto',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'fecha_nac' => 'date:Y-m-d',
            'estado' => 'string',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'user_rol',
            'id_user',
            'id_rol'
        );
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(
            Sucursal::class,
            'user_sucursal',
            'id_user',
            'id_sucursal'
        );
    }

    public function hasRole(string $rol): bool
    {
        $rol = mb_strtolower(
            trim($rol)
        );

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(
                static function (Rol $item) use ($rol): bool {
                    return
                        mb_strtolower(
                            trim(
                                (string) $item->rol
                            )
                        ) === $rol
                        &&
                        mb_strtoupper(
                            trim(
                                (string) $item->estado
                            )
                        ) === 'ACTIVO';
                }
            );
        }

        return $this->roles()
            ->whereRaw(
                'LOWER(rol.rol) = ?',
                [$rol]
            )
            ->where(
                'rol.estado',
                'Activo'
            )
            ->exists();
    }

    /**
     * @param array<int, string> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $rol) {
            if ($this->hasRole($rol)) {
                return true;
            }
        }

        return false;
    }

    public function isActive(): bool
    {
        return mb_strtoupper(
            trim(
                (string) $this->estado
            )
        ) === 'ACTIVO';
    }
}
