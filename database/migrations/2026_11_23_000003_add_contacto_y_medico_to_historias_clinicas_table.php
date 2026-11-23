<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 23/11/2026: lo que faltaba frente al formato de historia clínica odontológica
 * que revisó el usuario: estado civil, tratamiento médico actual, contacto de
 * emergencia y médico de cabecera. Todo opcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->string('estado_civil', 20)->nullable()->after('ocupacion');
            $table->text('tratamiento_medico_actual')->nullable()->after('medicamentos_actuales');
            $table->string('contacto_emergencia_nombre', 150)->nullable()->after('observaciones');
            $table->string('contacto_emergencia_parentesco', 50)->nullable()->after('contacto_emergencia_nombre');
            $table->string('contacto_emergencia_telefono', 20)->nullable()->after('contacto_emergencia_parentesco');
            $table->string('medico_cabecera_nombre', 150)->nullable()->after('contacto_emergencia_telefono');
            $table->string('medico_cabecera_telefono', 20)->nullable()->after('medico_cabecera_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->dropColumn([
                'estado_civil', 'tratamiento_medico_actual',
                'contacto_emergencia_nombre', 'contacto_emergencia_parentesco', 'contacto_emergencia_telefono',
                'medico_cabecera_nombre', 'medico_cabecera_telefono',
            ]);
        });
    }
};
