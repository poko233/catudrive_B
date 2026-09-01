<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Security\SecurityConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accion extends Model
{
    protected $table = 'accion';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | Catálogo protegido
    |--------------------------------------------------------------------------
    |
    | Las acciones forman parte de la infraestructura RBAC del sistema.
    | No deben administrarse mediante asignación masiva.
    |
    */

    protected $guarded = ['*'];

    public const ID_VER = 1;
    public const ID_CREAR = 2;
    public const ID_EDITAR = 3;
    public const ID_ELIMINAR = 4;

    public const VER = SecurityConfig::ACTION_VER;
    public const CREAR = SecurityConfig::ACTION_CREAR;
    public const EDITAR = SecurityConfig::ACTION_EDITAR;
    public const ELIMINAR = SecurityConfig::ACTION_ELIMINAR;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'accion' => 'string',
        ];
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(
            FormularioPermiso::class,
            'id_accion'
        );
    }

    /**
     * @return array<int, string>
     */
    public static function catalogo(): array
    {
        return [
            self::ID_VER => self::VER,
            self::ID_CREAR => self::CREAR,
            self::ID_EDITAR => self::EDITAR,
            self::ID_ELIMINAR => self::ELIMINAR,
        ];
    }

    /**
     * @return array<int, int>
     */
    public static function ids(): array
    {
        return array_keys(
            self::catalogo()
        );
    }

    /**
     * @return array<int, string>
     */
    public static function nombres(): array
    {
        return array_values(
            self::catalogo()
        );
    }

    public static function esIdValido(int $id): bool
    {
        return array_key_exists(
            $id,
            self::catalogo()
        );
    }

    public static function esNombreValido(string $accion): bool
    {
        return SecurityConfig::isValidAction(
            $accion
        );
    }

    public static function nombrePorId(int $id): ?string
    {
        return self::catalogo()[$id] ?? null;
    }

    public static function idPorNombre(string $accion): ?int
    {
        $normalizada = SecurityConfig::normalizeAction(
            $accion
        );

        if ($normalizada === null) {
            return null;
        }

        $id = array_search(
            $normalizada,
            self::catalogo(),
            true
        );

        return $id === false
            ? null
            : (int) $id;
    }
}
