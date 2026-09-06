<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    protected $table = 'detalle_venta';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_venta',
        'id_asiento',
        'id_pasajero',
        'precio_unitario',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_venta' => 'integer',
            'id_asiento' => 'integer',
            'id_pasajero' => 'integer',
            'precio_unitario' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(Asiento::class, 'id_asiento');
    }

    public function pasajero(): BelongsTo
    {
        return $this->belongsTo(Pasajero::class, 'id_pasajero');
    }
}