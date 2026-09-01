<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormularioPermiso extends Model
{
    protected $table = 'formulario_permiso';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_rol',
        'id_modulo',
        'id_formulario',
        'id_accion',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_rol' => 'integer',
            'id_modulo' => 'integer',
            'id_formulario' => 'integer',
            'id_accion' => 'integer',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(
            Rol::class,
            'id_rol'
        );
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(
            Modulo::class,
            'id_modulo'
        );
    }

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(
            Formulario::class,
            'id_formulario'
        );
    }

    public function accion(): BelongsTo
    {
        return $this->belongsTo(
            Accion::class,
            'id_accion'
        );
    }
}
