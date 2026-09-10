<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encomienda extends Model
{
    use SoftDeletes;

    protected $table = 'encomienda';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'guia',
        'id_ruta',
        'remitente',
        'destinatario',
        'descripcion',
        'cantidad',
        'precio',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_ruta' => 'integer',
            'cantidad' => 'integer',
            'precio' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
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

    public function viajeEncomienda(): HasOne
    {
        return $this->hasOne(
            ViajeEncomienda::class,
            'id_encomienda',
            'id'
        );
    }

    public function estaRegistrada(): bool
    {
        return $this->estado === 'Registrada';
    }

    public function estaEnTransito(): bool
    {
        return $this->estado === 'En tránsito';
    }

    public function estaEntregada(): bool
    {
        return $this->estado === 'Entregada';
    }

    public function estaAnulada(): bool
    {
        return $this->estado === 'Anulada';
    }
}