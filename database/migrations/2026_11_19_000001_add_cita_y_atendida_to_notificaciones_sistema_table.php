<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Panel "Por atender": cada aviso de cita recuerda a qué cita (y por lo tanto a
 * qué paciente) se refiere para mostrar su avatar, y los avisos que piden una
 * acción guardan cuándo el personal los dio por atendidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones_sistema', function (Blueprint $table) {
            // nullOnDelete: si se borra la cita, el aviso se conserva sin avatar.
            $table->foreignId('id_cita')->nullable()->after('user_id')->constrained('citas')->nullOnDelete();
            $table->timestamp('atendida_en')->nullable()->after('vista_en');
            $table->index(['user_id', 'atendida_en']);
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones_sistema', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'atendida_en']);
            $table->dropConstrainedForeignId('id_cita');
            $table->dropColumn('atendida_en');
        });
    }
};
