<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuestionario de historia clínica (anamnesis), una por paciente (22/11/2026).
 * Lo llenan la doctora y el personal autorizado desde el panel. Las listas
 * (antecedentes, alergias, hábitos…) guardan solo las claves marcadas "sí";
 * las opciones y sus nombres viven en App\Models\HistoriaClinica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historias_clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->unique()->constrained('pacientes')->cascadeOnDelete();

            // Datos generales
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sexo', 20)->nullable();
            $table->string('ocupacion', 100)->nullable();
            $table->string('tipo_sangre', 5)->nullable();

            // Antecedentes
            $table->json('antecedentes_familiares')->nullable();
            $table->text('antecedentes_familiares_notas')->nullable();
            $table->json('antecedentes_personales')->nullable();
            $table->text('antecedentes_personales_notas')->nullable();

            // Alergias y medicamentos
            $table->json('alergias')->nullable();
            $table->text('alergias_otras')->nullable();
            $table->text('medicamentos_actuales')->nullable();

            // Embarazo y lactancia (solo aplica a mujeres)
            $table->boolean('embarazo')->nullable();
            $table->boolean('lactancia')->nullable();

            // Antecedentes dentales y hábitos
            $table->json('antecedentes_dentales')->nullable();
            $table->string('ultima_visita_dentista', 100)->nullable();
            $table->text('antecedentes_dentales_notas')->nullable();
            $table->json('habitos')->nullable();
            $table->text('habitos_notas')->nullable();

            $table->text('observaciones')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historias_clinicas');
    }
};
