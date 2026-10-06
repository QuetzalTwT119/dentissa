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
        Schema::create('horarios_atencion', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('dia_semana')->unique();
            $table->boolean('abierto')->default(true);
            $table->unsignedTinyInteger('hora_apertura')->default(8);
            $table->unsignedTinyInteger('hora_cierre')->default(18);
            $table->unsignedTinyInteger('capacidad_simultanea')->default(2);
            $table->timestamps();
        });

        // dia_semana sigue la convención de Carbon::dayOfWeek (0=domingo..6=sábado).
        // Se insertan los 7 días con valores por defecto para que cualquier entorno
        // (incluidas las bases de datos de prueba recreadas con RefreshDatabase)
        // arranque siempre con una semana completa configurada.
        $ahora = now();

        DB::table('horarios_atencion')->insert([
            ['dia_semana' => 0, 'abierto' => false, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['dia_semana' => 1, 'abierto' => true, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['dia_semana' => 2, 'abierto' => true, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['dia_semana' => 3, 'abierto' => true, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['dia_semana' => 4, 'abierto' => true, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['dia_semana' => 5, 'abierto' => true, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['dia_semana' => 6, 'abierto' => true, 'hora_apertura' => 9, 'hora_cierre' => 14, 'capacidad_simultanea' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_atencion');
    }
};
