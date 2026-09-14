<?php

declare(strict_types=1);

namespace App\Modules\Auth\Observers;

use App\Shared\Models\User;
use App\Shared\Services\QrService;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserObserver
{
    public function __construct(
        private readonly QrService $qrService,
    ) {
    }

    /**
     * Se dispara justo después de que el usuario se persiste en BD.
     * En ese momento ya tenemos $user->id disponible.
     */
    public function created(User $user): void
    {
        $this->generateQrForUser($user);
    }

    private function generateQrForUser(User $user): void
    {
        /*
        |--------------------------------------------------------------------------
        | Si ya tiene QR, no lo regeneramos
        |--------------------------------------------------------------------------
        |
        | Los seeders o imports con datos preexistentes pueden traerlo ya.
        |
        */

        if (!empty($user->codigo_qr)) {
            return;
        }

        try {
            $qrDataUrl = $this->qrService->generateQrImage(
                (int) $user->id
            );

            /*
            |--------------------------------------------------------------------------
            | saveQuietly
            |--------------------------------------------------------------------------
            |
            | - Evita re-disparar eventos updated (recursión + ruido de auditoría).
            | - El trait Auditable no registrará este cambio interno.
            |
            */

            $user->codigo_qr = $qrDataUrl;
            $user->saveQuietly();

            /*
            | Actualizamos el atributo en memoria para que el controller
            | pueda devolver el QR en la respuesta de registro sin reload.
            */
            $user->syncOriginalAttribute('codigo_qr');
        } catch (Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Fail-safe deliberado
            |--------------------------------------------------------------------------
            |
            | El registro del usuario NO debe romperse porque el QR falle.
            | Pero SÍ logueamos el error real para diagnóstico.
            |
            | Si ves este log, la causa probable es:
            |  - QR_SECRET_KEY ausente o mal formada (.env)
            |  - Extensión GD de PHP no instalada
            |
            */

            Log::error(
                'No se pudo generar el código QR automáticamente.',
                [
                    'user_id' => $user->id,
                    'usuario' => $user->usuario,
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}