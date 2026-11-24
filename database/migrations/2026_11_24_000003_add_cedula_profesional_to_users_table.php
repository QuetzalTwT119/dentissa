<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receta en PDF (24/11/2026): en México la receta debe llevar la cédula
 * profesional de quien la emite. Cada doctora o doctor la escribe en Mi perfil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cedula_profesional', 20)->nullable()->after('telefono');
            $table->string('especialidad', 100)->nullable()->after('cedula_profesional');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cedula_profesional', 'especialidad']);
        });
    }
};
