<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token de la app móvil (26/11/2026). Se guarda solo su huella SHA-256: un
 * volcado de la base no permite usarlo. Uno por usuario; un nuevo inicio de
 * sesión reemplaza al anterior y «cerrar sesión» lo borra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('app_token', 64)->nullable()->unique()->after('remember_token');
            $table->timestamp('app_token_expira_en')->nullable()->after('app_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['app_token']);
            $table->dropColumn(['app_token', 'app_token_expira_en']);
        });
    }
};
