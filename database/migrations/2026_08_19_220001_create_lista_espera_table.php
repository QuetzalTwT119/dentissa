<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lista_espera', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('telefono', 20);
            $table->string('email', 100)->nullable();
            $table->foreignId('id_servicio')->nullable()->constrained('servicios')->nullOnDelete();
            $table->foreignId('id_tratamiento')->nullable()->constrained('tratamientos')->nullOnDelete();
            $table->date('fecha_preferida')->nullable()->index();
            $table->enum('franja_horaria', ['cualquiera', 'manana', 'tarde'])->default('cualquiera');
            $table->enum('estado', ['pendiente', 'contactada', 'cerrada'])->default('pendiente')->index();
            $table->foreignId('contactado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_contacto')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lista_espera');
    }
};
