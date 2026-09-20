<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encomienda extends Model
{
    use SoftDeletes;

    protected $table = 'encomienda';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'guia', 'qr_token', 'id_remitente', 'id_destinatario', 'id_user_registro',
        'concepto', 'subtotal', 'descuento', 'total', 'lugar_pago', 'estado_pago',
        'tipo_pago', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_remitente' => 'integer',
            'id_destinatario' => 'integer',
            'id_user_registro' => 'integer',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function remitente(): BelongsTo { return $this->belongsTo(Cliente::class, 'id_remitente', 'id'); }
    public function destinatario(): BelongsTo { return $this->belongsTo(Cliente::class, 'id_destinatario', 'id'); }
    public function usuarioRegistro(): BelongsTo { return $this->belongsTo(User::class, 'id_user_registro', 'id'); }
    public function detalles(): HasMany { return $this->hasMany(DetalleEncomienda::class, 'id_encomienda', 'id'); }
    public function viajeEncomienda(): HasOne { return $this->hasOne(ViajeEncomienda::class, 'id_encomienda', 'id'); }

    public function estaEnOrigen(): bool { return $this->estado === 'En origen'; }
    public function estaEnTransito(): bool { return $this->estado === 'En tránsito'; }
    public function estaEnDestino(): bool { return $this->estado === 'En destino'; }
    public function estaEntregada(): bool { return $this->estado === 'Entregada'; }
    public function estaAnulada(): bool { return $this->estado === 'Anulada'; }
    public function estaPagada(): bool { return $this->estado_pago === 'Pagado'; }
    public function estaPendientePago(): bool { return $this->estado_pago === 'Pendiente'; }
}
