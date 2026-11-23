<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 22/11/2026, Control de actividades:
 *
 * 1. Los eventos con detalle (accion con punto: "oferta.eliminada") no
 *    guardaban la hora desde la aplicación; la ponía la base de datos en SU
 *    zona horaria (hora universal en MySQL/Railway). Se mostraban 6 horas
 *    adelantados. Se corrigen restando la diferencia entre la zona de la base
 *    y la de la aplicación.
 * 2. Cada cambio quedaba dos veces: el evento con detalle y uno genérico del
 *    observador ("eliminado", sin detalle). Se borran los genéricos que tienen
 *    su evento con detalle (misma tabla, registro y usuario, segundos de
 *    diferencia).
 *
 * Solo MySQL: en SQLite (pruebas) la hora ya la pone la aplicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Minutos de la zona de la base de datos respecto a UTC, y de la app.
        $minutosBase = (int) DB::selectOne('SELECT TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), NOW()) AS m')->m;
        $minutosApp = now()->utcOffset();
        $ajuste = $minutosApp - $minutosBase;

        if ($ajuste !== 0) {
            DB::update("UPDATE logs_auditoria SET fecha = DATE_ADD(fecha, INTERVAL ? MINUTE) WHERE accion LIKE '%.%'", [$ajuste]);
        }

        DB::delete(<<<'SQL'
            DELETE generico FROM logs_auditoria AS generico
            JOIN logs_auditoria AS detalle
              ON detalle.tabla = generico.tabla
             AND detalle.registro_id = generico.registro_id
             AND detalle.id_usuario <=> generico.id_usuario
             AND detalle.accion LIKE '%.%'
             AND ABS(TIMESTAMPDIFF(SECOND, detalle.fecha, generico.fecha)) <= 5
            WHERE generico.accion IN ('creado', 'actualizado', 'eliminado')
        SQL);
    }

    public function down(): void
    {
        // Corrección de datos: no se deshace (los duplicados no aportaban nada).
    }
};
