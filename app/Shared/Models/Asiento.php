<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asiento extends Model
{
    protected $table = 'asiento';

    protected $fillable = [
        'id_piso',
        'fila',
        'columna',
        'tipo_celda',
        'numero_asiento',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_piso' => 'integer',
            'fila' => 'integer',
            'columna' => 'integer',
            'numero_asiento' => 'integer',
            'tipo_celda' => 'string',
            'estado' => 'string',
        ];
    }

    public function piso(): BelongsTo
    {
        return $this->belongsTo(Piso::class, 'id_piso');
    }
}