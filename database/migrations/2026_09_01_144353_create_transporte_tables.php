<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── Categoría de vehículo ─────────────────────────────────
        Schema::create('categoria_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->string('categoria', 255);
            $table->timestamps();
        });

        // ─── Vehículo ─────────────────────────────────────────────
        Schema::create('vehiculo', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 255)->unique();
            $table->string('tipo', 255);
            $table->string('marca', 255);
            $table->string('modelo', 255);
            $table->string('color', 255)->nullable();
            $table->string('capacidad', 255)->nullable();
            $table->enum('estado', ['Operativo', 'En mantenimiento', 'Baja'])->default('Operativo');
            $table->timestamps();

            $table->index('estado');
        });

        // ─── Chofer ───────────────────────────────────────────────
        Schema::create('chofer', function (Blueprint $table) {
            $table->foreignId('id')
                ->primary()
                ->constrained('user')
                ->onDelete('restrict');
            $table->string('carnet_sindical', 255)->nullable();
            $table->string('numero_licencia', 255)->nullable();
            $table->string('categoria_licencia', 255)->nullable();
            $table->timestamps();
        });

        // ─── Ruta ─────────────────────────────────────────────────
        Schema::create('ruta', function (Blueprint $table) {
            $table->id();
            $table->string('origen', 255);
            $table->string('destino', 255);
            $table->timestamp('hora_inicio')->nullable();
            $table->timestamp('hora_fin')->nullable();
            $table->decimal('tarifa', 10, 2)->default(0);
            $table->enum('estado', ['Activa', 'Inactiva'])->default('Activa');
            $table->timestamps();

            $table->index('estado');
        });

        // ─── Asignación vehículo-chofer (histórico) ──────────────
        Schema::create('asignacion_vehiculo_chofer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_chofer')
                ->constrained('chofer')
                ->onDelete('restrict');
            $table->foreignId('id_vehiculo')
                ->constrained('vehiculo')
                ->onDelete('restrict');
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_chofer');
            $table->index('id_vehiculo');
            $table->index(['id_chofer', 'id_vehiculo', 'estado']);
        });

        // ─── Vehículo-Chofer-Ruta (viaje / asignación a ruta) ─────
        Schema::create('vehiculo_chofer_ruta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asignacion_vehiculo_chofer')
                ->constrained('asignacion_vehiculo_chofer')
                ->onDelete('restrict');
            $table->foreignId('id_ruta')
                ->constrained('ruta')
                ->onDelete('restrict');
            $table->timestamp('hora_inicio')->nullable(); // hora de salida
            $table->timestamps();

            $table->index('id_asignacion_vehiculo_chofer');
            $table->index('id_ruta');
            $table->unique(
                ['id_asignacion_vehiculo_chofer', 'id_ruta', 'hora_inicio'],
                'uq_asig_ruta_hora'
            );
        });

        // ─── Pasajero (independiente de user) ─────────────────────
        Schema::create('pasajero', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 255);
            $table->string('apellido_paterno', 255);
            $table->string('apellido_materno', 255)->nullable();
            $table->string('ci', 255)->nullable();
            $table->timestamps();

            $table->index('ci');
        });

        // ─── Asiento (por vehículo) ──────────────────────────────
        Schema::create('asiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_vehiculo')
                ->constrained('vehiculo')
                ->onDelete('restrict');
            $table->string('tipo_asiento', 255)->nullable();
            $table->timestamps();

            $table->index('id_vehiculo');
        });

        // ─── Propietario (relación chofer-vehículo, 1 vehículo → 1 dueño) ─
        Schema::create('propietario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_chofer')
                ->constrained('chofer')
                ->onDelete('restrict');
            $table->foreignId('id_vehiculo')
                ->constrained('vehiculo')
                ->onDelete('restrict');
            $table->timestamps();

            $table->index('id_chofer');
            $table->index('id_vehiculo');
            $table->softDeletes();

        });

        // ─── Encomienda ───────────────────────────────────────────
        Schema::create('encomienda', function (Blueprint $table) {
            $table->id();
            $table->string('guia', 255)->nullable();
            $table->string('remitente', 255)->nullable();
            $table->string('destinatario', 255)->nullable();
            $table->string('origen', 255)->nullable();
            $table->string('destino', 255)->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('cantidad')->default(1);
            $table->decimal('precio', 10, 2)->default(0);
            $table->enum('estado', ['Registrada', 'En tránsito', 'Entregada', 'Anulada'])->default('Registrada');
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
        });

        // ─── Venta ────────────────────────────────────────────────
        Schema::create('venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_vehiculo_chofer_ruta')
                ->constrained('vehiculo_chofer_ruta')
                ->onDelete('restrict');
            $table->foreignId('id_user')
                ->constrained('user')
                ->onDelete('restrict');
            $table->index('id_user');
            $table->string('forma_pago', 255)->nullable();
            $table->decimal('precio_total', 10, 2)->default(0);
            $table->enum('estado', ['Pendiente', 'Pagada', 'Anulada'])->default('Pendiente');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_vehiculo_chofer_ruta');
            $table->index('estado');
        });

        // ─── Detalle de venta ─────────────────────────────────────
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_venta')
                ->constrained('venta')
                ->onDelete('restrict');

            $table->foreignId('id_asiento')
                ->constrained('asiento')
                ->onDelete('restrict');

            $table->foreignId('id_pasajero')
                ->constrained('pasajero')
                ->onDelete('restrict');

            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->timestamps();

            $table->index('id_venta');
            $table->index('id_asiento');
            $table->index('id_pasajero');
        });


        // ─── Encomienda por viaje (N:M) ───────────────────────────
        Schema::create('vehiculo_chofer_ruta_encomienda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_vehiculo_chofer_ruta')
                ->constrained('vehiculo_chofer_ruta')
                ->onDelete('restrict');
            $table->foreignId('id_encomienda')
                ->constrained('encomienda')
                ->onDelete('restrict');
            $table->timestamps();

            $table->index('id_vehiculo_chofer_ruta');
            $table->index('id_encomienda');
            $table->unique(
                ['id_vehiculo_chofer_ruta', 'id_encomienda'],
                'uq_viaje_encomienda'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_chofer_ruta_encomienda');
        Schema::dropIfExists('detalle_venta');
        Schema::dropIfExists('venta');
        Schema::dropIfExists('encomienda');
        Schema::dropIfExists('propietario');
        Schema::dropIfExists('asiento');
        Schema::dropIfExists('pasajero');
        Schema::dropIfExists('vehiculo_chofer_ruta');
        Schema::dropIfExists('asignacion_vehiculo_chofer');
        Schema::dropIfExists('ruta');
        Schema::dropIfExists('chofer');
        Schema::dropIfExists('vehiculo');
        Schema::dropIfExists('categoria_vehiculo');
    }
};