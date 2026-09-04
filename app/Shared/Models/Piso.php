<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piso extends Model
{
    protected $table = 'piso';

    protected $fillable = [
        'id_vehiculo',
        'numero',
        'nombre',
        'filas',
        'columnas',
        'orden',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_vehiculo' => 'integer',
            'numero' => 'integer',
            'filas' => 'integer',
            'columnas' => 'integer',
            'orden' => 'integer',
            'estado' => 'string',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }

    public function asientos(): HasMany
    {
        return $this->hasMany(Asiento::class, 'id_piso');
    }
}