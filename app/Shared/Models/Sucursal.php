<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use App\Shared\Traits\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sucursal extends Model
{
    use Auditable;
    use BelongsToEmpresa;

    protected $table = 'sucursal';

    protected $primaryKey = 'id';

    public $timestamps = true;

    /*
    |--------------------------------------------------------------------------
    | Esta tabla SÍ contiene id_empresa.
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'id_empresa',
        'sucursal',
        'responsable',
        'direccion',
        'longitud',
        'latitud',
        'telefono',
        'celular',
        'email',
        'pais',
        'ciudad',
        'localidad',
        'imagen',
        'estado',
        'api_token',
    ];

    protected $hidden = [      // ← AÑADIR bloque si no existe
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_empresa' => 'integer',
            'estado' => 'string',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(
            Empresa::class,
            'id_empresa'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_sucursal',
            'id_sucursal',
            'id_user'
        );
    }

    protected function auditResourceName(): string
    {
        return 'Sucursal';
    }
    /*
    |--------------------------------------------------------------------------
    | Auditable: excluir api_token del audit log
    |--------------------------------------------------------------------------
    */
    protected function auditIgnoredAttributes(): array    // ← AÑADIR si no existe
    {
        return [
            'created_at',
            'updated_at',
            'remember_token',
            'api_token',
        ];
    }
}
