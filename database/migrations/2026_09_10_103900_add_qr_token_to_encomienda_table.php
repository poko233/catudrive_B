<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encomienda', function (Blueprint $table): void {
            $table->string('qr_token', 64)->nullable()->unique();
        });

        DB::table('viaje_encomienda')
            ->select('id_encomienda')
            ->orderBy('id_encomienda')
            ->get()
            ->each(function ($relacion): void {
                DB::table('encomienda')
                    ->where('id', $relacion->id_encomienda)
                    ->whereNull('qr_token')
                    ->update([
                        'qr_token' => Str::random(64),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('encomienda', function (Blueprint $table): void {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
