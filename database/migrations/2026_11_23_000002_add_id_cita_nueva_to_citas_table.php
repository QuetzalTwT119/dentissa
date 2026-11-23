<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 23/11/2026: cuando el paciente reagenda desde el recordatorio, la cita vieja
 * (cancelada para liberar el horario) apunta a la nueva. Así la agenda y el bot
 * dicen "Reagendada → 25 nov, 08:00" en lugar de solo "Cancelada".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->foreignId('id_cita_nueva')->nullable()->after('asistencia_confirmada_en')
                ->constrained('citas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_cita_nueva');
        });
    }
};
