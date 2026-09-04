<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaVehiculo extends Model
{
    protected $table = 'categoria_vehiculo';

    protected $fillable = [
        'categoria',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'id_categoria');
    }
}