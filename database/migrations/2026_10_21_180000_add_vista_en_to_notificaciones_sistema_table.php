<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones_sistema', function (Blueprint $table) {
            $table->timestamp('vista_en')->nullable()->after('leida_en');
            $table->index(['user_id', 'vista_en', 'created_at']);
        });

        // Los avisos existentes no deben aparecer como novedades después de actualizar.
        DB::table('notificaciones_sistema')->whereNull('vista_en')->update([
            'vista_en' => DB::raw('COALESCE(leida_en, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('notificaciones_sistema', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'vista_en', 'created_at']);
            $table->dropColumn('vista_en');
        });
    }
};
