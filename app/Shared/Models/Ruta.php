<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    protected $table =
        'ruta';

    protected $primaryKey =
        'id';

    public $timestamps =
        true;

    protected $fillable = [
        'origen',
        'destino',
        'hora_inicio',
        'hora_fin',
        'tarifa',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' =>
                'integer',

            'hora_inicio' =>
                'datetime',

            'hora_fin' =>
                'datetime',

            'tarifa' =>
                'decimal:2',

            'estado' =>
                'string',
        ];
    }
}