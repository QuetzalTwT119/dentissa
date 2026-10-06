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
        Schema::table('imagenes', function (Blueprint $table) {
            $table->enum('tipo', ['galeria', 'servicio', 'tratamiento', 'testimonio', 'perfil', 'historial_clinico'])
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imagenes', function (Blueprint $table) {
            $table->enum('tipo', ['galeria', 'servicio', 'tratamiento', 'testimonio', 'perfil'])
                ->nullable()
                ->change();
        });
    }
};
