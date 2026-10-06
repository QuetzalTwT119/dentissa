<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('nombre', 100)->nullable()->after('id_usuario');
            $table->string('apellido', 100)->nullable()->after('nombre');
            $table->string('telefono', 20)->nullable()->after('apellido');
            $table->string('email', 100)->nullable()->unique()->after('telefono');
        });

        // UPDATE...JOIN es sintaxis de MySQL; se recorre fila por fila para que
        // la migración también funcione en SQLite (usado en las pruebas).
        DB::table('pacientes')->whereNotNull('id_usuario')->orderBy('id')
            ->chunkById(100, function ($pacientes) {
                foreach ($pacientes as $paciente) {
                    $usuario = DB::table('users')->find($paciente->id_usuario);

                    if ($usuario) {
                        DB::table('pacientes')->where('id', $paciente->id)->update([
                            'nombre' => $usuario->nombre,
                            'apellido' => $usuario->apellido,
                            'telefono' => $usuario->telefono,
                            'email' => $usuario->email,
                        ]);
                    }
                }
            });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropForeign(['id_usuario']);
            $table->dropUnique(['id_usuario']);
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario')->nullable()->change();
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->foreign('id_usuario')->references('id')->on('users')->nullOnDelete();
        });

        // Al ya no poder iniciar sesión, se elimina la cuenta de usuario de cada
        // paciente. El FK de arriba (nullOnDelete) deja su fila de `pacientes`
        // intacta — con eso sobreviven sus citas, historial y testimonios — solo
        // se pierde la capacidad de iniciar sesión.
        DB::table('users')->where('rol', 'paciente')->delete();

        // La columna `rol` de `users` se queda con 'paciente' como valor legal
        // a nivel de base de datos (ya nunca se usa: el enum de PHP ya no tiene
        // ese caso y no queda ninguna fila con ese valor). Alterar un ENUM de
        // MySQL in-place es frágil entre motores (MySQL vs SQLite, usado en las
        // pruebas) y no aporta nada más que limpieza cosmética.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropForeign(['id_usuario']);
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario')->nullable(false)->change();
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->unique('id_usuario');
            $table->foreign('id_usuario')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['nombre', 'apellido', 'telefono', 'email']);
        });
    }
};
