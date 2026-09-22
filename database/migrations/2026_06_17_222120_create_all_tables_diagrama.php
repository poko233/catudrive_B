<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── Autenticación y tokens ─────────────────────────────────
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->string('correo');
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
            $table->index('correo');
            $table->index('code');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // ─── Empresa ───────────────────────────────────────────────
        Schema::create('empresa', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 100);
            $table->text('slogan')->nullable();
            $table->string('sigla', 200)->nullable();
            $table->string('telefono', 11)->nullable();
            $table->string('celular', 11)->nullable();
            $table->string('email', 80)->nullable();
            $table->text('direccion')->nullable();
            $table->string('responsable', 80)->nullable();
            $table->string('latitud', 80)->nullable();
            $table->string('longitud', 80)->nullable();
            $table->text('objeto')->nullable();
            $table->text('mision')->nullable();
            $table->text('vision')->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->string('facebook', 40)->nullable();
            $table->string('instagram', 40)->nullable();
            $table->string('tiktok', 40)->nullable();
            $table->string('linkedin', 40)->nullable();
            $table->string('carrito', 8)->nullable();
            $table->decimal('tipo_cambio', 10, 2)->nullable();
            $table->string('logo_cuadrado', 255)->nullable();
            $table->string('logo_largo', 255)->nullable();
            $table->string('baner_inicio', 255)->nullable();
            $table->string('icono', 255)->nullable();
            $table->string('titulo_cierre', 80)->nullable();
            $table->text('mensaje_cierre')->nullable();
            $table->string('titulo_inicio', 80)->nullable();
            $table->text('mensaje_inicio')->nullable();
            $table->string('dominio', 200)->nullable();
            $table->string('smtp_correo', 100)->nullable();
            $table->string('correo_institucional', 80)->nullable();
            $table->string('pwd_institucional', 80)->nullable();
            $table->timestamps();
        });

        // ─── Usuario ──────────────────────────────────────────────
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('usuario', 40)->unique();
            $table->string('password', 80);
            $table->string('ci', 12)->unique();
            $table->string('nombres', 40);
            $table->string('primer_apellido', 50);
            $table->string('segundo_apellido', 50)->nullable();
            $table->enum('genero', ['Masculino', 'Femenino', 'Otro'])->nullable();
            $table->date('fecha_nac')->nullable();
            $table->string('email', 80)->nullable();
            $table->string('telefono', 10)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('direccion', 50)->nullable();
            $table->enum('expedido', ['LPZ', 'CBBA', 'OR', 'PT', 'TJ', 'SCZ', 'BN', 'PD', 'CH', 'QR', 'EXT'])->nullable();
            $table->text('codigo_qr')->nullable();
            $table->string('verificacion', 40)->nullable();
            $table->string('foto', 80)->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();

            $table->index(['usuario', 'ci']);
        });

        // ─── Acción (permisos) ────────────────────────────────────
        Schema::create('accion', function (Blueprint $table) {
            $table->id();
            $table->text('accion');
        });

        // ─── Sucursal ─────────────────────────────────────────────
        Schema::create('sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_empresa')
                ->constrained('empresa')
                ->onDelete('cascade');
            $table->string('sucursal', 40);
            $table->string('responsable', 40)->nullable();
            $table->string('direccion', 80)->nullable();
            $table->string('longitud', 40)->nullable();
            $table->string('latitud', 40)->nullable();
            $table->string('telefono', 10)->nullable();
            $table->string('celular', 10)->nullable();
            $table->string('email', 40)->nullable();
            $table->string('pais', 20)->nullable();
            $table->string('ciudad', 20)->nullable();
            $table->string('localidad', 30)->nullable();
            $table->string('imagen', 255)->nullable();
            $table->string('qr', 255)->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();

            $table->index('id_empresa');
        });

        // ─── Rol ─────────────────────────────────
        Schema::create('rol', function (Blueprint $table) {
            $table->id();
            $table->string('rol', 40)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();
        });

        // ─── Módulo ──────────────────────────────
        Schema::create('modulo', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 40);
            $table->text('descripcion')->nullable();
            $table->text('icono')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();

            $table->index('orden');
            $table->index(['estado', 'orden']);
        });

        // ─── Formulario ──────────────────────────
        Schema::create('formulario', function (Blueprint $table) {
            $table->id();
            $table->string('formulario', 40);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->string('ruta', 40)->nullable();
            $table->timestamps();
        });

        // ─── Pivotes de usuario ───────────────────────────────────
        Schema::create('user_sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('user')->onDelete('cascade');
            $table->foreignId('id_sucursal')->constrained('sucursal')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_user', 'id_sucursal']);
        });

        Schema::create('user_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('user')->onDelete('cascade');
            $table->foreignId('id_rol')->constrained('rol')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_user', 'id_rol']);
        });

        // ─── Pivotes de permisos ──────────────────────────────────
        Schema::create('modulo_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')->constrained('rol')->onDelete('cascade');
            $table->foreignId('id_modulo')->constrained('modulo')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_rol', 'id_modulo']);
        });

        Schema::create('formulario_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_modulo')->constrained('modulo')->onDelete('cascade');
            $table->foreignId('id_formulario')->constrained('formulario')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_modulo', 'id_formulario']);
        });

        Schema::create('formulario_permiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')->constrained('rol')->onDelete('cascade');
            $table->foreignId('id_modulo')->constrained('modulo')->onDelete('cascade');
            $table->foreignId('id_formulario')->constrained('formulario')->onDelete('cascade');
            $table->foreignId('id_accion')->constrained('accion')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_rol', 'id_modulo', 'id_formulario', 'id_accion'], 'form_permiso_unique');
        });

        Schema::create('formulario_accion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')
                ->constrained('rol')
                ->onDelete('cascade');
            $table->foreignId('id_formulario')
                ->constrained('formulario')
                ->onDelete('cascade');
            $table->string('selector_html', 200);
            $table->boolean('habilitado')->default(true);
            $table->timestamps();

            $table->index(['id_rol', 'id_formulario']);
        });

        // ─── Tipo de transacción ──────────────────────────────────
        Schema::create('tipo_transaccion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('transaccion', 80);
            $table->enum('tipo_transaccion', ['Ingreso', 'Egreso'])->default('Ingreso');
            $table->timestamps();
        });

        // ─── Arqueo ───────────────────────────────────────
        Schema::create('arqueo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')
                ->constrained('user')
                ->onDelete('restrict');

            $table->dateTime('fecha_apertura');
            $table->dateTime('fecha_cierre')->nullable();
            $table->decimal('saldo_anterior', 10, 2)->default(0);

            $table->decimal('total_efectivo', 10, 2)->nullable();
            $table->decimal('total_tarjeta', 10, 2)->nullable();
            $table->decimal('total_qr', 10, 2)->nullable();
            $table->decimal('total_transferencia', 10, 2)->nullable();
            $table->decimal('total_general', 10, 2)->nullable();

            $table->unsignedInteger('billete_200')->default(0);
            $table->unsignedInteger('billete_100')->default(0);
            $table->unsignedInteger('billete_50')->default(0);
            $table->unsignedInteger('billete_20')->default(0);
            $table->unsignedInteger('billete_10')->default(0);
            $table->unsignedInteger('moneda_5')->default(0);
            $table->unsignedInteger('moneda_2')->default(0);
            $table->unsignedInteger('moneda_1')->default(0);
            $table->unsignedInteger('moneda_50_ctvs')->default(0);
            $table->unsignedInteger('moneda_20_ctvs')->default(0);
            $table->unsignedInteger('moneda_10_ctvs')->default(0);

            $table->enum('estado', ['Iniciado', 'Terminado'])->default('Iniciado');
            $table->timestamps();
            $table->softDeletes();
            $table->index('estado');
            $table->index(['id_user', 'fecha_apertura']);
        });

        // ─── Ingreso ──────────────────────────────────────
        Schema::create('ingreso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')
                ->constrained('user')
                ->onDelete('restrict');
            $table->foreignId('id_arqueo')
                ->constrained('arqueo')
                ->onDelete('restrict');
            $table->foreignId('id_tipo_transaccion')
                ->constrained('tipo_transaccion')
                ->onDelete('restrict');

            $table->enum('tipo_pago', [
                'Efectivo',
                'Tarjeta',
                'QR',
                'Transferencia',
            ]);

            $table->dateTime('fecha_registro');
            $table->text('detalle');
            $table->decimal('monto', 10, 2);
            $table->enum('estado', ['Valido', 'Anulado'])->default('Valido');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_user');
            $table->index('id_tipo_transaccion');
            $table->index('estado');
            $table->index(['id_arqueo', 'estado']);
        });

        // ─── Egreso ───────────────────────────────────────
        Schema::create('egreso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')
                ->constrained('user')
                ->onDelete('restrict');
            $table->foreignId('id_arqueo')
                ->constrained('arqueo')
                ->onDelete('restrict');
            $table->foreignId('id_tipo_transaccion')
                ->constrained('tipo_transaccion')
                ->onDelete('restrict');

            $table->enum('tipo_pago', [
                'Efectivo',
                'Tarjeta',
                'QR',
                'Transferencia',
            ]);

            $table->dateTime('fecha_registro');
            $table->text('detalle');
            $table->decimal('monto', 10, 2);
            $table->enum('estado', ['Valido', 'Anulado'])->default('Valido');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_user');
            $table->index('id_tipo_transaccion');
            $table->index('estado');
            $table->index(['id_arqueo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egreso');
        Schema::dropIfExists('ingreso');
        Schema::dropIfExists('arqueo');
        Schema::dropIfExists('tipo_transaccion');
        Schema::dropIfExists('formulario_accion');
        Schema::dropIfExists('formulario_permiso');
        Schema::dropIfExists('formulario_modulo');
        Schema::dropIfExists('modulo_rol');
        Schema::dropIfExists('user_rol');
        Schema::dropIfExists('user_sucursal');
        Schema::dropIfExists('formulario');
        Schema::dropIfExists('modulo');
        Schema::dropIfExists('rol');
        Schema::dropIfExists('sucursal');
        Schema::dropIfExists('accion');
        Schema::dropIfExists('user');
        Schema::dropIfExists('empresa');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_codes');
    }
};