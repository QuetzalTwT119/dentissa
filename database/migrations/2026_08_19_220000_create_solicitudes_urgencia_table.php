<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_urgencia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('telefono', 20);
            $table->string('email', 100)->nullable();
            $table->text('motivo');
            $table->json('senales_reportadas')->nullable();
            $table->enum('estado', ['pendiente', 'contactada', 'cerrada'])->default('pendiente')->index();
            $table->foreignId('contactado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_contacto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_urgencia');
    }
};
