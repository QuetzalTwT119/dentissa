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
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('id_servicio')->nullable()->constrained('servicios')->nullOnDelete();
            $table->foreignId('id_tratamiento')->nullable()->constrained('tratamientos')->nullOnDelete();
            $table->dateTime('fecha_hora');
            $table->integer('duracion_estimada');
            $table->enum('estado', [
                'agendada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio',
            ])->default('agendada');
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('confirmada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->foreignId('cancelada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
