<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 21/11/2026: "activa" (sí/no) pasa a "estado" (pendiente | activa | inactiva)
 * para que una oferta nueva quede En revisión antes de verse en la portada.
 * Las que ya existían conservan cómo se veían: activa → activa, no → inactiva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->after('imagen');
        });

        DB::table('ofertas')->update([
            'estado' => DB::raw("CASE WHEN activa THEN 'activa' ELSE 'inactiva' END"),
        ]);

        Schema::table('ofertas', function (Blueprint $table) {
            $table->dropColumn('activa');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->boolean('activa')->default(true)->after('imagen');
        });

        DB::table('ofertas')->update([
            'activa' => DB::raw("CASE WHEN estado = 'activa' THEN TRUE ELSE FALSE END"),
        ]);

        Schema::table('ofertas', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn('estado');
        });
    }
};
