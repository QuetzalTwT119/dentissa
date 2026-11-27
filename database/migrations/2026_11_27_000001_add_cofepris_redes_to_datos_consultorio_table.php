<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 27/11/2026: pie de la portada con datos sanitarios y redes sociales (ideas
 * tomadas de sitios de clínicas de referencia). Todo opcional: vacío, el pie
 * no muestra esa línea ni ese ícono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->string('cofepris', 60)->nullable()->after('vision');
            $table->string('instagram', 200)->nullable()->after('cofepris');
            $table->string('facebook', 200)->nullable()->after('instagram');
            $table->string('tiktok', 200)->nullable()->after('facebook');
        });
    }

    public function down(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->dropColumn(['cofepris', 'instagram', 'facebook', 'tiktok']);
        });
    }
};
