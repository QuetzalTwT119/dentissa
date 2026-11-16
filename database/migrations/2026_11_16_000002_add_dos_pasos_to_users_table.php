<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verificación en dos pasos (TOTP) del personal.
 *
 * - dos_pasos_secreto: secreto TOTP, cifrado con APP_KEY (cast `encrypted`).
 * - dos_pasos_recuperacion: hashes de los códigos de recuperación de un solo uso.
 * - dos_pasos_confirmado_en: la verificación solo cuenta cuando el usuario
 *   comprobó un código; antes de eso no se pide al iniciar sesión.
 * - dos_pasos_ultimo_paso: último intervalo de 30 s aceptado, para que el
 *   mismo código no sirva dos veces (ataque de repetición).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('dos_pasos_secreto')->nullable();
            $table->text('dos_pasos_recuperacion')->nullable();
            $table->timestamp('dos_pasos_confirmado_en')->nullable();
            $table->unsignedBigInteger('dos_pasos_ultimo_paso')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dos_pasos_secreto', 'dos_pasos_recuperacion', 'dos_pasos_confirmado_en', 'dos_pasos_ultimo_paso']);
        });
    }
};
