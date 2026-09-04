<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsignacionVehiculoChofer extends Model
{
    use SoftDeletes;

    protected $table =
        'asignacion_vehiculo_chofer';

    protected $primaryKey =
        'id';

    public $timestamps =
        true;

    protected $fillable = [
        'id_chofer',
        'id_vehiculo',
        'fecha_asignacion',
        'fecha_finalizacion',
        'observacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' =>
                'integer',

            'id_chofer' =>
                'integer',

            'id_vehiculo' =>
                'integer',

            'fecha_asignacion' =>
                'date',

            'fecha_finalizacion' =>
                'date',

            'estado' =>
                'string',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CHOFER
    |--------------------------------------------------------------------------
    */

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(
            Chofer::class,
            'id_chofer',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VEHÍCULO
    |--------------------------------------------------------------------------
    */

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(
            Vehiculo::class,
            'id_vehiculo',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADO
    |--------------------------------------------------------------------------
    */

    public function estaActiva(): bool
    {
        return mb_strtoupper(
            trim(
                (string)
                $this->estado
            )
        ) === 'ACTIVO';
    }
}