<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recordatorio 24 h antes de la cita y respuesta del paciente.
 *
 * - recordatorio_enviado_en: evita mandar el recordatorio dos veces cuando el
 *   comando corre cada 15 minutos. Reagendar la vuelve NULL para que el
 *   paciente reciba un aviso con la nueva fecha.
 * - asistencia_confirmada_en: el paciente respondió "Asistiré". Se guarda
 *   aparte del estado porque las citas públicas ya nacen Confirmadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->timestamp('recordatorio_enviado_en')->nullable()->after('motivo_cancelacion');
            $table->timestamp('asistencia_confirmada_en')->nullable()->after('recordatorio_enviado_en');
            $table->index(['estado', 'fecha_hora', 'recordatorio_enviado_en'], 'citas_recordatorio_index');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropIndex('citas_recordatorio_index');
            $table->dropColumn(['recordatorio_enviado_en', 'asistencia_confirmada_en']);
        });
    }
};
