<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Egreso extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $table = 'egreso';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_user',
        'id_arqueo',
        'id_tipo_transaccion',
        'tipo_pago',
        'fecha_registro',
        'detalle',
        'monto',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_user' => 'integer',
            'id_arqueo' => 'integer',
            'id_tipo_transaccion' => 'integer',
            'tipo_pago' => 'string',
            'fecha_registro' => 'datetime',
            'monto' => 'decimal:2',
            'estado' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function arqueo(): BelongsTo
    {
        return $this->belongsTo(Arqueo::class, 'id_arqueo');
    }

    public function tipoTransaccion(): BelongsTo
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }

    protected function auditResourceName(): string
    {
        return 'Egreso';
    }
}