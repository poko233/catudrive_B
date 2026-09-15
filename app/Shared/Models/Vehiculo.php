<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehiculo extends Model
{
    protected $table = 'vehiculo';

    protected $fillable = [
        'id_categoria',
        'placa',
        'tipo',
        'marca',
        'modelo',
        'color',
        'capacidad',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_categoria' => 'integer',
            'capacidad' => 'integer',
            'estado' => 'string',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaVehiculo::class, 'id_categoria');
    }

    public function pisos(): HasMany
    {
        return $this->hasMany(Piso::class, 'id_vehiculo');
    }

    public function asientos(): HasManyThrough
    {
        return $this->hasManyThrough(
            Asiento::class,
            Piso::class,
            'id_vehiculo', // Foreign key en piso
            'id_piso'      // Foreign key en asiento
        );
    }
    public function propietario(): HasOne
    {
        return $this->hasOne(Propietario::class, 'id_vehiculo');
    }
    public function asignaciones(): HasMany
    {
        return $this->hasMany(
            AsignacionVehiculoChofer::class,
            'id_vehiculo'
        );
    }
}