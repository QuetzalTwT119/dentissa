<?php

namespace Database\Seeders;

use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Database\Seeder;

/**
 * Catálogo real de la clínica, tomado de la libreta de la doctora.
 *
 * Los precios se dejan vacíos a propósito: la clínica no los ha confirmado y
 * mostrar cifras inventadas confundiría al paciente.
 */
class ServiciosTratamientosSeeder extends Seeder
{
    /** Servicios y tratamientos del catálogo de ejemplo anterior (se desactivan, no se borran). */
    private const SERVICIOS_DE_EJEMPLO = ['Ortodoncia', 'Implantología', 'Periodoncia', 'Endodoncia', 'Odontopediatría'];

    private const TRATAMIENTOS_DE_EJEMPLO = [
        'Carillas de Porcelana', 'Limpieza Dental Profunda', 'Endodoncia (Conducto)', 'Implante Dental',
        'Ortodoncia (Brackets)', 'Extracción Simple', 'Selladores de Fisuras',
    ];

    public function run(): void
    {
        $catalogo = [
            'Odontología General' => [
                'orden' => 1,
                'tratamientos' => [
                    ['nombre' => 'Valoración', 'categoria' => 'Diagnóstico', 'duracion_estimada' => 30],
                    ['nombre' => 'Limpieza Dental', 'categoria' => 'Preventiva', 'duracion_estimada' => 60],
                    ['nombre' => 'Retiro de Caries', 'categoria' => 'Restauradora', 'duracion_estimada' => 60],
                    ['nombre' => 'Extracción Dental', 'categoria' => 'Quirúrgica', 'duracion_estimada' => 60],
                ],
            ],
            'Odontología Estética' => [
                'orden' => 2,
                'tratamientos' => [
                    ['nombre' => 'Blanqueamiento Dental', 'categoria' => 'Estética', 'duracion_estimada' => 90],
                ],
            ],
        ];

        foreach ($catalogo as $nombreServicio => $datos) {
            $servicio = Servicio::updateOrCreate(
                ['nombre' => $nombreServicio],
                ['orden' => $datos['orden'], 'activo' => true],
            );

            $vinculos = [];
            foreach ($datos['tratamientos'] as $indice => $tratamiento) {
                $modelo = Tratamiento::updateOrCreate(
                    ['nombre' => $tratamiento['nombre']],
                    [...$tratamiento, 'precio_referencia' => null, 'activo' => true],
                );
                $vinculos[$modelo->id] = ['orden' => $indice + 1];
            }

            $servicio->tratamientos()->syncWithoutDetaching($vinculos);
        }

        // Solo se desactiva lo que venía en el catálogo de ejemplo; lo que la
        // clínica cree desde el panel no se toca y el historial de citas se conserva.
        Servicio::whereIn('nombre', self::SERVICIOS_DE_EJEMPLO)->update(['activo' => false]);
        Tratamiento::whereIn('nombre', self::TRATAMIENTOS_DE_EJEMPLO)->update(['activo' => false]);
    }
}
