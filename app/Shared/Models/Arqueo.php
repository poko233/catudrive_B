<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arqueo extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $table = 'arqueo';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_user',
        'fecha_apertura',
        'fecha_cierre',
        'saldo_anterior',
        'total_efectivo',
        'total_tarjeta',
        'total_qr',
        'total_transferencia',
        'total_general',
        'billete_200',
        'billete_100',
        'billete_50',
        'billete_20',
        'billete_10',
        'moneda_5',
        'moneda_2',
        'moneda_1',
        'moneda_50_ctvs',
        'moneda_20_ctvs',
        'moneda_10_ctvs',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_user' => 'integer',
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
            'saldo_anterior' => 'decimal:2',
            'total_efectivo' => 'decimal:2',
            'total_tarjeta' => 'decimal:2',
            'total_qr' => 'decimal:2',
            'total_transferencia' => 'decimal:2',
            'total_general' => 'decimal:2',
            'billete_200' => 'integer',
            'billete_100' => 'integer',
            'billete_50' => 'integer',
            'billete_20' => 'integer',
            'billete_10' => 'integer',
            'moneda_5' => 'integer',
            'moneda_2' => 'integer',
            'moneda_1' => 'integer',
            'moneda_50_ctvs' => 'integer',
            'moneda_20_ctvs' => 'integer',
            'moneda_10_ctvs' => 'integer',
            'estado' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'id_arqueo');
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'id_arqueo');
    }

    protected function auditResourceName(): string
    {
        return 'Arqueo';
    }
}