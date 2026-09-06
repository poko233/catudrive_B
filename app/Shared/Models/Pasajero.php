<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pasajero extends Model
{
    protected $table = 'pasajero';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'ci',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'ci' => 'string',
        ];
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'id_pasajero');
    }
}