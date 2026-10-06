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
        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->text('descripcion')->nullable();
            $table->string('categoria', 50)->nullable();
            $table->integer('duracion_estimada')->nullable();
            $table->decimal('precio_referencia', 10, 2)->nullable();
            $table->integer('sesiones_requeridas')->default(1);
            $table->boolean('requiere_autorizacion')->default(false);
            $table->boolean('activo')->default(true);
            $table->string('imagen')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tratamientos');
    }
};
