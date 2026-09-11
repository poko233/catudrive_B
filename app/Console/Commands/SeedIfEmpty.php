<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedIfEmpty extends Command
{
    /**
     * Ejecuta DatabaseSeeder solo si el sistema está vacío.
     *
     * Idempotente: correrlo N veces no cambia el estado final
     * más allá de la primera siembra.
     */
    protected $signature = 'app:seed-if-empty';

    protected $description = 'Ejecuta DatabaseSeeder únicamente si la base de datos no contiene los datos base.';

    public function handle(): int
    {
        /*
        |--------------------------------------------------------------------------
        | Comprobación de estado
        |--------------------------------------------------------------------------
        |
        | Si CUALQUIERA de las tablas base ya tiene filas, asumimos
        | que el sistema fue sembrado alguna vez y NO re-sembramos.
        |
        | Importante: no usamos count() sobre 'user' porque el usuario
        | 'admin' podría haber sido renombrado/eliminado manualmente
        | sin que eso signifique que la BD está virgen.
        |
        | Usamos las tres tablas de catálogo que el seeder garantiza
        | crear en su primera ejecución.
        |
        */

        $yaSembrado =
            DB::table('rol')->exists() ||
            DB::table('modulo')->exists() ||
            DB::table('accion')->exists();

        if ($yaSembrado) {
            $this->info('ℹ️  Sistema ya sembrado. Omitiendo DatabaseSeeder.');
            return self::SUCCESS;
        }

        $this->info('🌱 Sistema vacío detectado. Ejecutando DatabaseSeeder...');

        /*
         * --force es obligatorio en producción (Laravel bloquea seeders
         * interactivos cuando APP_ENV=production).
         */
        $exitCode = $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\DatabaseSeeder',
            '--force' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            $this->error('❌ El DatabaseSeeder falló. Revisa los logs.');
            return self::FAILURE;
        }

        $this->info('✅ Siembra inicial completada.');
        return self::SUCCESS;
    }
}