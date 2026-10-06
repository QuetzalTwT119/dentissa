<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SQLite (usado en las pruebas) no soporta agregar restricciones CHECK a
     * una tabla existente vía ALTER TABLE — solo MySQL (producción) las
     * aplica. La validación 1-5 ya existe en TestimonioController; esto es
     * una segunda capa de defensa a nivel de base de datos, no la única.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE testimonios ADD CONSTRAINT chk_testimonios_calificacion CHECK (calificacion IS NULL OR calificacion BETWEEN 1 AND 5)',
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE testimonios DROP CONSTRAINT chk_testimonios_calificacion');
        }
    }
};
