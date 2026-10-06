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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'nombre');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido', 100)->after('nombre')->default('');
            $table->string('telefono', 20)->after('email')->default('');
            $table->enum('rol', ['paciente', 'asistente', 'admin'])->default('paciente')->after('password');
            $table->string('foto_perfil')->nullable()->after('rol');
            $table->timestamp('ultimo_acceso')->nullable()->after('foto_perfil');
            $table->boolean('activo')->default(true)->after('ultimo_acceso');
            $table->boolean('telefono_verificado')->default(false)->after('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'apellido',
                'telefono',
                'rol',
                'foto_perfil',
                'ultimo_acceso',
                'activo',
                'telefono_verificado',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nombre', 'name');
        });
    }
};
