<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formulario extends Model
{
    use Auditable;

    protected $table = 'formulario';

    protected $primaryKey = 'id';

    public $timestamps = true;

    /*
    |--------------------------------------------------------------------------
    | La tabla formulario actual NO contiene id_empresa.
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'formulario',
        'descripcion',
        'estado',
        'ruta',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'estado' => 'string',
        ];
    }

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(
            Modulo::class,
            'formulario_modulo',
            'id_formulario',
            'id_modulo'
        );
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(
            FormularioPermiso::class,
            'id_formulario'
        );
    }

    public function reglasVisibilidad(): HasMany
    {
        return $this->hasMany(
            FormularioAccion::class,
            'id_formulario'
        );
    }

    protected function auditResourceName(): string
    {
        return 'Formulario';
    }
}
