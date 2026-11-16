<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidencia del consentimiento del paciente al aviso de privacidad.
 *
 * Se guarda la fecha y la versión del aviso que aceptó: si el aviso cambia,
 * se sabe qué texto aceptó cada paciente. Se actualiza cada vez que agenda
 * por la web o por Telegram.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->timestamp('aviso_privacidad_aceptado_en')->nullable();
            $table->string('aviso_privacidad_version', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn(['aviso_privacidad_aceptado_en', 'aviso_privacidad_version']);
        });
    }
};
