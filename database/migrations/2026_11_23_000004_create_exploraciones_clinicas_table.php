<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 23/11/2026: exploración clínica odontológica (parte 2 del formato de historia
 * clínica que revisó el usuario). Varias por paciente: la inicial y las de
 * control, para comparar el índice de O'Leary y el odontograma en el tiempo.
 * Lo llena la doctora. Las listas se guardan como JSON.
 *
 * Además, cada consulta puede decir en qué diente(s) se trabajó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exploraciones_clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('id_doctor')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->json('estomatologico')->nullable();
            $table->text('atm')->nullable();
            $table->json('periodontal')->nullable();
            $table->string('higiene_oral', 10)->nullable();
            $table->unsignedSmallInteger('oleary_dientes')->nullable();
            $table->unsignedSmallInteger('oleary_superficies_placa')->nullable();
            $table->json('pulpares')->nullable();
            $table->json('radiografica')->nullable();
            $table->text('otros_hallazgos')->nullable();
            $table->json('odontograma')->nullable();
            $table->text('diagnosticos')->nullable();
            $table->timestamps();

            $table->index(['id_paciente', 'fecha']);
        });

        Schema::table('historial_clinico', function (Blueprint $table) {
            $table->string('dientes', 100)->nullable()->after('tratamiento_realizado');
        });
    }

    public function down(): void
    {
        Schema::table('historial_clinico', function (Blueprint $table) {
            $table->dropColumn('dientes');
        });
        Schema::dropIfExists('exploraciones_clinicas');
    }
};
