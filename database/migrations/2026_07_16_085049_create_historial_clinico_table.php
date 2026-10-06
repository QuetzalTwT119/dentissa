<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('historial_clinico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('id_cita')->nullable()->constrained('citas')->nullOnDelete();
            $table->foreignId('id_doctor')->constrained('users')->cascadeOnDelete();
            $table->date('fecha_consulta');
            $table->text('motivo_consulta');
            $table->text('diagnostico')->nullable();
            $table->text('tratamiento_realizado')->nullable();
            $table->text('medicamentos_recetados')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('notas_doctor')->nullable();
            $table->enum('tipo_consulta', ['primera_vez', 'seguimiento', 'urgencia', 'control'])->default('primera_vez');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_clinico');
    }
};
