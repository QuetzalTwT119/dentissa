<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nombre del negocio en Google Maps (25/11/2026). Con solo la dirección, Maps
 * marca un punto en la calle; con el nombre abre la ficha del consultorio
 * (foto, horario, teléfono). Vacío = se busca solo la dirección.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->string('nombre_maps', 100)->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->dropColumn('nombre_maps');
        });
    }
};
