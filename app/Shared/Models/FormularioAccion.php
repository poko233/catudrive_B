<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormularioAccion extends Model
{
    protected $table = 'formulario_accion';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_rol',
        'id_formulario',
        'selector_html',
        'habilitado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_rol' => 'integer',
            'id_formulario' => 'integer',
            'habilitado' => 'boolean',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(
            Rol::class,
            'id_rol'
        );
    }

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(
            Formulario::class,
            'id_formulario'
        );
    }
}
