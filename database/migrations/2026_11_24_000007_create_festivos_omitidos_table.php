<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Festivos que la doctora decidió trabajar (24/11/2026). Cuando quita con 🗑
 * el bloqueo de un festivo, la fecha queda aquí y dentissa:bloquear-festivos
 * ya no lo vuelve a poner en su corrida mensual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festivos_omitidos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->foreignId('omitido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('festivos_omitidos');
    }
};
