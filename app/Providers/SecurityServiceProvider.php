<?php

namespace App\Providers;

use App\Shared\Security\RateLimiters;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios dentro del contenedor de Laravel.
     *
     * Aquí posteriormente podremos registrar servicios
     * compartidos de seguridad si es necesario.
     */
    public function register(): void
    {
        //
    }

    /**
     * Se ejecuta cuando Laravel ya terminó de registrar
     * sus servicios principales.
     *
     * Aquí registramos todos nuestros RateLimiters.
     */
    public function boot(): void
    {
        RateLimiters::register();
    }
}