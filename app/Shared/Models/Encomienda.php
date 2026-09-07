<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encomienda extends Model
{
    use SoftDeletes;

    protected $table =
        'encomienda';

    protected $primaryKey =
        'id';

    public $timestamps =
        true;

    protected $fillable = [
        'guia',
        'remitente',
        'destinatario',
        'origen',
        'destino',
        'descripcion',
        'cantidad',
        'precio',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' =>
                'integer',

            'cantidad' =>
                'integer',

            'precio' =>
                'decimal:2',

            'estado' =>
                'string',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ASIGNACIÓN A VIAJE
    |--------------------------------------------------------------------------
    */

    public function asignacionViaje(): HasOne
    {
        return $this->hasOne(
            VehiculoChoferRutaEncomienda::class,
            'id_encomienda',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADOS
    |--------------------------------------------------------------------------
    */

    public function estaRegistrada(): bool
    {
        return mb_strtoupper(
            trim(
                (string)
                $this->estado
            )
        ) === 'REGISTRADA';
    }

    public function estaEnTransito(): bool
    {
        return mb_strtoupper(
            trim(
                (string)
                $this->estado
            )
        ) === 'EN TRÁNSITO';
    }

    public function estaEntregada(): bool
    {
        return mb_strtoupper(
            trim(
                (string)
                $this->estado
            )
        ) === 'ENTREGADA';
    }

    public function estaAnulada(): bool
    {
        return mb_strtoupper(
            trim(
                (string)
                $this->estado
            )
        ) === 'ANULADA';
    }
}