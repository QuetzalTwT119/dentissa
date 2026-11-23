<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 22/11/2026: roles super_admin / doctor / asistente (antes admin / asistente).
 *
 * users.rol era enum('paciente','asistente','admin'): no aceptaba los roles
 * nuevos. Pasa a texto corto (la lista válida la da App\Enums\RolUsuario) y
 * las cuentas "admin" pasan a "super_admin" (la doctora dueña conserva todo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('asistente')->change();
        });

        DB::table('users')->where('rol', 'admin')->update(['rol' => 'super_admin']);
    }

    public function down(): void
    {
        DB::table('users')->whereIn('rol', ['super_admin', 'doctor'])->update(['rol' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['paciente', 'asistente', 'admin'])->default('paciente')->change();
        });
    }
};
