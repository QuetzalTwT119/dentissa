<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pagos (24/11/2026): cuánto, cómo y cuándo pagó cada paciente, ligado a una
 * cita si aplica. Sin cobro en línea ni facturación: solo el registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('id_cita')->nullable()->constrained('citas')->nullOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('metodo', 20);
            $table->date('fecha');
            $table->string('concepto', 150)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
