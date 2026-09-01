<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use Auditable;

    protected $table = 'empresa';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'empresa',
        'slogan',
        'sigla',
        'telefono',
        'celular',
        'email',
        'direccion',
        'responsable',
        'latitud',
        'longitud',
        'objeto',
        'mision',
        'vision',
        'estado',
        'facebook',
        'instagram',
        'tiktok',
        'linkedin',
        'carrito',
        'tipo_cambio',
        'titulo_cierre',
        'mensaje_cierre',
        'titulo_inicio',
        'mensaje_inicio',
        'dominio',
        'smtp_correo',
        'correo_institucional',
        'pwd_institucional',
    ];

    /*
    |--------------------------------------------------------------------------
    | Datos sensibles
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'pwd_institucional',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'tipo_cambio' => 'decimal:2',
            'estado' => 'string',
        ];
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(
            Sucursal::class,
            'id_empresa'
        );
    }

    protected function auditIgnoredAttributes(): array
    {
        return [
            'created_at',
            'updated_at',
            'remember_token',
            'pwd_institucional',
        ];
    }

    protected function auditResourceName(): string
    {
        return 'Empresa';
    }
}
