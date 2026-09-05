<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $table = 'venta';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_viaje',
        'id_user',
        'forma_pago',
        'precio_total',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_viaje' => 'integer',
            'id_user' => 'integer',
            'precio_total' => 'decimal:2',
            'estado' => 'string',
        ];
    }

    public function viaje(): BelongsTo
    {
        return $this->belongsTo(Viaje::class, 'id_viaje');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'id_venta');
    }
}