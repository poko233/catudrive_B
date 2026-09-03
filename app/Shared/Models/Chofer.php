<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chofer extends Model
{
    protected $table = 'chofer';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'carnet_sindical',
        'numero_licencia',
        'categoria_licencia',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id',
            'id'
        );
    }
}