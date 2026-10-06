<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `notificaciones.id_usuario` nació obligatorio porque en su momento el
     * paciente también era un `user`. Desde que se desacopló (ver
     * 2026_07_22_120000_desacoplar_pacientes_de_usuarios), un recordatorio o
     * confirmación de cita va dirigido a un `paciente`, no a un `user` — así
     * que se agrega `id_paciente` (nullable) y se libera `id_usuario` para que
     * cada notificación apunte a uno de los dos, no a ambos.
     */
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->foreignId('id_paciente')->nullable()->after('id_usuario')
                ->constrained('pacientes')->cascadeOnDelete();
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropForeign(['id_usuario']);
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario')->nullable()->change();
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->foreign('id_usuario')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropForeign(['id_paciente']);
            $table->dropColumn('id_paciente');
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropForeign(['id_usuario']);
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario')->nullable(false)->change();
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->foreign('id_usuario')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
