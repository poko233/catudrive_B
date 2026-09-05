<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Viaje extends Model
{
    use Auditable;

    protected $table = 'viaje';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_vehiculo_chofer_ruta',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_vehiculo_chofer_ruta' => 'integer',
            'estado' => 'string',
        ];
    }

    public function vehiculoChoferRuta(): BelongsTo
    {
        return $this->belongsTo(VehiculoChoferRuta::class, 'id_vehiculo_chofer_ruta');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_viaje');
    }
}