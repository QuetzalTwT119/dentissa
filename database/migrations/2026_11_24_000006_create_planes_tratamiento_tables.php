<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan de tratamiento por paciente (24/11/2026): lista de procedimientos
 * (resina en el 36, extracción del 48…) marcados como pendientes o hechos. El
 * avance se calcula solo: hechos / total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_tratamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('id_doctor')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 120);
            $table->string('estado', 20)->default('activo');
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_tratamiento_pasos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_plan')->constrained('planes_tratamiento')->cascadeOnDelete();
            $table->foreignId('id_tratamiento')->nullable()->constrained('tratamientos')->nullOnDelete();
            $table->string('descripcion', 150);
            $table->string('dientes', 100)->nullable();
            $table->decimal('costo', 10, 2)->nullable();
            $table->date('hecho_en')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_tratamiento_pasos');
        Schema::dropIfExists('planes_tratamiento');
    }
};
