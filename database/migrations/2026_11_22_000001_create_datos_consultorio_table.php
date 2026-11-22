<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 22/11/2026: datos de contacto del consultorio editables desde el panel
 * (Configuración → Datos del consultorio). Antes solo existían como variables
 * de entorno: si la doctora perdía el celular o cambiaba de número, había que
 * esperar a que alguien con acceso a Railway las cambiara.
 *
 * Una sola fila. Un campo vacío usa el valor de las variables de entorno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datos_consultorio', function (Blueprint $table) {
            $table->id();
            $table->string('telefono', 20)->nullable();
            $table->string('whatsapp', 15)->nullable();
            $table->string('correo', 100)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_consultorio');
    }
};
