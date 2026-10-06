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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->enum('tipo', ['whatsapp', 'email', 'push'])->nullable();
            $table->enum('canal', [
                'cita_confirmada', 'cita_recordatorio', 'cita_cancelada', 'cita_reagendada', 'promocion', 'general',
            ])->nullable();
            $table->string('titulo', 100)->nullable();
            $table->text('mensaje')->nullable();
            $table->boolean('leido')->default(false);
            $table->timestamp('fecha_envio')->useCurrent();
            $table->timestamp('fecha_lectura')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
