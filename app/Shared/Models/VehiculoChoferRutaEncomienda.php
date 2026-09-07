<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoChoferRutaEncomienda extends Model
{
    protected $table =
        'vehiculo_chofer_ruta_encomienda';

    protected $primaryKey =
        'id';

    public $timestamps =
        true;

    protected $fillable = [
        'id_vehiculo_chofer_ruta',
        'id_encomienda',
    ];

    protected function casts(): array
    {
        return [
            'id' =>
                'integer',

            'id_vehiculo_chofer_ruta' =>
                'integer',

            'id_encomienda' =>
                'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VIAJE
    |--------------------------------------------------------------------------
    */

    public function viaje(): BelongsTo
    {
        return $this->belongsTo(
            VehiculoChoferRuta::class,
            'id_vehiculo_chofer_ruta',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ENCOMIENDA
    |--------------------------------------------------------------------------
    */

    public function encomienda(): BelongsTo
    {
        return $this->belongsTo(
            Encomienda::class,
            'id_encomienda',
            'id'
        );
    }
}