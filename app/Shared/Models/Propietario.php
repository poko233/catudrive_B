<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Propietario extends Model
{
    use SoftDeletes;

    protected $table = 'propietario';

    protected $fillable = [
        'id_chofer',
        'id_vehiculo',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_chofer' => 'integer',
            'id_vehiculo' => 'integer',
        ];
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'id_chofer');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }
}