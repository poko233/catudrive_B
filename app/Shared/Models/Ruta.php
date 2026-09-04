<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'fecha_inicio',
        'hora_inicio',
        'fecha_fin',
        'hora_fin',
        'tarifa',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' =>
                'integer',

            'fecha_inicio' =>
                'date',

            'fecha_fin' =>
                'date',

            'tarifa' =>
                'decimal:2',

            'estado' =>
                'string',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VIAJES / ASIGNACIONES A RUTA
    |--------------------------------------------------------------------------
    */

    public function viajes(): HasMany
    {
        return $this->hasMany(
            VehiculoChoferRuta::class,
            'id_ruta',
            'id'
        );
    }
}