<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    protected $table =
        'password_reset_codes';

    protected $primaryKey =
        'id';

    public $timestamps =
        true;

    protected $fillable = [
        'correo',
        'code',
        'expires_at',
        'used',
    ];

    protected function casts(): array
    {
        return [
            'id' =>
                'integer',

            'expires_at' =>
                'datetime',

            'used' =>
                'boolean',
        ];
    }
}