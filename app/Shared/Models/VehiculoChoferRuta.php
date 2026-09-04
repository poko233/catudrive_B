<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoChoferRuta extends Model
{
    protected $table =
        'vehiculo_chofer_ruta';

    protected $primaryKey =
        'id';

    public $timestamps =
        true;

    protected $fillable = [
        'id_asignacion_vehiculo_chofer',
        'id_ruta',
        'hora_inicio',
    ];

    protected function casts(): array
    {
        return [
            'id' =>
                'integer',

            'id_asignacion_vehiculo_chofer' =>
                'integer',

            'id_ruta' =>
                'integer',

            'hora_inicio' =>
                'datetime',
        ];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(
            Ruta::class,
            'id_ruta',
            'id'
        );
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(
            AsignacionVehiculoChofer::class,
            'id_asignacion_vehiculo_chofer',
            'id'
        );
    }
}