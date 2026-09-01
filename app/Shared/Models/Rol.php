<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use Auditable;

    protected $table = 'rol';

    protected $primaryKey = 'id';

    public $timestamps = true;

    /*
    |--------------------------------------------------------------------------
    | La tabla rol actual NO contiene id_empresa.
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'rol',
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'estado' => 'string',
        ];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_rol',
            'id_rol',
            'id_user'
        );
    }

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(
            Modulo::class,
            'modulo_rol',
            'id_rol',
            'id_modulo'
        );
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(
            FormularioPermiso::class,
            'id_rol'
        );
    }

    public function reglasVisibilidad(): HasMany
    {
        return $this->hasMany(
            FormularioAccion::class,
            'id_rol'
        );
    }

    protected function auditResourceName(): string
    {
        return 'Rol';
    }
}
