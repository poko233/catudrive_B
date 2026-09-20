<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoTransaccion extends Model
{
    use Auditable;

    protected $table = 'tipo_transaccion';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'codigo',
        'transaccion',
        'tipo_transaccion',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'tipo_transaccion' => 'string',
        ];
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'id_tipo_transaccion');
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'id_tipo_transaccion');
    }

    protected function auditResourceName(): string
    {
        return 'TipoTransaccion';
    }
}