<?php

declare(strict_types=1);
namespace App\Shared\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class Cliente extends Model { protected $table='cliente'; protected $fillable=['nombres','apellido_paterno','apellido_materno','ci','telefono']; protected function casts(): array { return ['id'=>'integer']; }
 public function encomiendasComoRemitente(): HasMany { return $this->hasMany(Encomienda::class,'id_remitente'); }
 public function encomiendasComoDestinatario(): HasMany { return $this->hasMany(Encomienda::class,'id_destinatario'); }
}
