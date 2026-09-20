<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleEncomienda extends Model
{
    protected $table = 'detalle_encomienda';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['id_encomienda', 'detalle', 'cantidad', 'precio_unitario'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_encomienda' => 'integer',
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
        ];
    }

    public function encomienda(): BelongsTo
    {
        return $this->belongsTo(Encomienda::class, 'id_encomienda', 'id');
    }
}
