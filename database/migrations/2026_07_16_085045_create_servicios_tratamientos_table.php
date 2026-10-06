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
        Schema::create('servicios_tratamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_servicio')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('id_tratamiento')->constrained('tratamientos')->cascadeOnDelete();
            $table->integer('orden')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['id_servicio', 'id_tratamiento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios_tratamientos');
    }
};
