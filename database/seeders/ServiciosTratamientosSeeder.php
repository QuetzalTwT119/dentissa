<?php

namespace Database\Seeders;

use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Database\Seeder;

class ServiciosTratamientosSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $servicios = collect([
            ['nombre' => 'Odontología General', 'orden' => 1],
            ['nombre' => 'Odontología Estética', 'orden' => 2],
            ['nombre' => 'Ortodoncia', 'orden' => 3],
            ['nombre' => 'Implantología', 'orden' => 4],
            ['nombre' => 'Periodoncia', 'orden' => 5],
            ['nombre' => 'Endodoncia', 'orden' => 6],
            ['nombre' => 'Odontopediatría', 'orden' => 7],
        ])->mapWithKeys(fn (array $datos) => [
            $datos['nombre'] => Servicio::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [...$datos, 'activo' => true],
            ),
        ]);

        $tratamientos = collect([
            ['nombre' => 'Blanqueamiento Dental', 'categoria' => 'Estética', 'duracion_estimada' => 90, 'precio_referencia' => 350.00],
            ['nombre' => 'Carillas de Porcelana', 'categoria' => 'Estética', 'duracion_estimada' => 120, 'precio_referencia' => 800.00],
            ['nombre' => 'Limpieza Dental Profunda', 'categoria' => 'Preventiva', 'duracion_estimada' => 60, 'precio_referencia' => 150.00],
            ['nombre' => 'Endodoncia (Conducto)', 'categoria' => 'Restauradora', 'duracion_estimada' => 90, 'precio_referencia' => 400.00],
            ['nombre' => 'Implante Dental', 'categoria' => 'Quirúrgica', 'duracion_estimada' => 120, 'precio_referencia' => 1200.00],
            ['nombre' => 'Ortodoncia (Brackets)', 'categoria' => 'Estética', 'duracion_estimada' => 30, 'precio_referencia' => 600.00],
            ['nombre' => 'Extracción Simple', 'categoria' => 'Quirúrgica', 'duracion_estimada' => 45, 'precio_referencia' => 100.00],
            ['nombre' => 'Selladores de Fisuras', 'categoria' => 'Preventiva', 'duracion_estimada' => 30, 'precio_referencia' => 80.00],
        ])->mapWithKeys(fn (array $datos) => [
            $datos['nombre'] => Tratamiento::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [...$datos, 'activo' => true],
            ),
        ]);

        $asignaciones = [
            'Odontología Estética' => ['Blanqueamiento Dental', 'Carillas de Porcelana'],
            'Odontología General' => ['Limpieza Dental Profunda', 'Selladores de Fisuras', 'Extracción Simple'],
            'Ortodoncia' => ['Ortodoncia (Brackets)'],
            'Implantología' => ['Implante Dental'],
            'Endodoncia' => ['Endodoncia (Conducto)'],
        ];

        foreach ($asignaciones as $nombreServicio => $nombresTratamientos) {
            $servicios[$nombreServicio]->tratamientos()->syncWithoutDetaching(
                collect($nombresTratamientos)
                    ->values()
                    ->mapWithKeys(fn (string $nombre, int $indice) => [
                        $tratamientos[$nombre]->id => ['orden' => $indice + 1],
                    ])
                    ->all(),
            );
        }
    }
}
