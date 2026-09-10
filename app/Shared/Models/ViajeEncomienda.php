<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViajeEncomienda extends Model
{
    protected $table = 'viaje_encomienda';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_viaje',
        'id_encomienda',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_viaje' => 'integer',
            'id_encomienda' => 'integer',
        ];
    }

    public function viaje(): BelongsTo
    {
        return $this->belongsTo(Viaje::class, 'id_viaje', 'id');
    }

    public function encomienda(): BelongsTo
    {
        return $this->belongsTo(Encomienda::class, 'id_encomienda', 'id');
    }
}