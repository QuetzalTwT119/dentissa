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
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->foreignId('id_tratamiento')->nullable()->constrained('tratamientos')->nullOnDelete();
            $table->enum('tipo', ['descuento', 'paquete', 'bonificacion'])->default('descuento');
            $table->decimal('valor_descuento', 5, 2)->nullable();
            $table->enum('tipo_valor', ['porcentaje', 'monto_fijo'])->default('porcentaje');
            $table->decimal('precio_especial', 10, 2)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('cupo_total')->default(0);
            $table->integer('cupos_utilizados')->default(0);
            $table->string('imagen')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ofertas');
    }
};
