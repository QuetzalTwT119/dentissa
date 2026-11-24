<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consentimiento informado (24/11/2026): se registra qué se explicó al
 * paciente y se imprime para firmarlo a mano (NOM-004-SSA3-2012). Queda en el
 * expediente para volver a imprimirlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('id_doctor')->nullable()->constrained('users')->nullOnDelete();
            $table->string('procedimiento', 150);
            $table->string('dientes', 100)->nullable();
            $table->text('beneficios');
            $table->text('riesgos');
            $table->text('alternativas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos');
    }
};
