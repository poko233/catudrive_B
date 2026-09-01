<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    use Auditable;

    protected $table = 'modulo';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'modulo',
        'descripcion',
        'icono',
        'orden',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' =>
                'integer',

            'orden' =>
                'integer',

            'estado' =>
                'string',
        ];
    }

    public function formularios(): BelongsToMany
    {
        return $this->belongsToMany(
            Formulario::class,
            'formulario_modulo',
            'id_modulo',
            'id_formulario'
        );
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'modulo_rol',
            'id_modulo',
            'id_rol'
        );
    }

    public function formularioPermisos(): HasMany
    {
        return $this->hasMany(
            FormularioPermiso::class,
            'id_modulo'
        );
    }

    protected function auditResourceName(): string
    {
        return 'Modulo';
    }
}