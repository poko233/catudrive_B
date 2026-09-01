<?php

declare(strict_types=1);

namespace App\Modules\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $userName,
        public readonly int $expireMinutes,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Asunto
    |--------------------------------------------------------------------------
    */

    public function envelope(): Envelope
    {
        return new Envelope(
            subject:
                'Código para restablecer tu contraseña - '
                . config(
                    'app.name',
                    'MetaSoft'
                ),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Contenido
    |--------------------------------------------------------------------------
    */

    public function content(): Content
    {
        return new Content(
            view:
                'emails.reset-password',

            with: [
                'code' =>
                    $this->code,

                'userName' =>
                    $this->userName,

                'expireMinutes' =>
                    $this->expireMinutes,
            ],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Adjuntos
    |--------------------------------------------------------------------------
    */

    public function attachments(): array
    {
        return [];
    }
}