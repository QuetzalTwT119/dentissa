<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Reglas de consistencia que salieron del barrido visual del 19/11/2026:
 * un solo título principal por pantalla, campos de 44 px para el dedo,
 * solo colores de la paleta y el perfil sin desborde en celular.
 */
class ConsistenciaDisenoTest extends TestCase
{
    use RefreshDatabase;

    /** Formularios del panel que antes usaban h2 como título de la página. */
    private const FORMULARIOS = [
        'admin.servicios.create',
        'admin.tratamientos.create',
        'admin.ofertas.create',
        'admin.testimonios.create',
        'admin.preguntas-frecuentes.create',
        'admin.galeria.create',
        'admin.casos.create',
        'admin.usuarios.create',
        'citas.create',
        'notificaciones.index',
        'profile.edit',
    ];

    public function test_cada_formulario_tiene_un_solo_titulo_principal(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        foreach (self::FORMULARIOS as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, '<h1'), "{$ruta} debe tener exactamente un <h1>.");
        }
    }

    public function test_los_campos_de_texto_y_listas_miden_al_menos_44_px(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        foreach ([...self::FORMULARIOS, 'admin.pacientes.index', 'admin.usuarios.index', 'admin.auditoria.index', 'asistente-agenda.create', 'citas.index'] as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();
            preg_match_all('/<(?:input|select)\b[^>]*>/s', $html, $campos);

            foreach ($campos[0] as $campo) {
                if (preg_match('/type="(checkbox|radio|hidden|file|color|range)"/', $campo) || ! str_contains($campo, 'border-field')) {
                    continue;
                }

                $this->assertStringContainsString('min-h-11', $campo, "{$ruta}: campo sin altura táctil mínima: ".mb_substr($campo, 0, 120));
                $this->assertStringNotContainsString('sm:min-h-0', $campo, "{$ruta}: el campo no debe encogerse en escritorio.");
            }
        }
    }

    public function test_las_vistas_solo_usan_colores_de_la_paleta(): void
    {
        $fuera = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($archivo) => str_ends_with($archivo->getFilename(), '.blade.php'))
            ->filter(fn ($archivo) => preg_match('/\b(bg|text|border|ring)-(gray|indigo|blue|red|green|slate|zinc)-\d{2,3}\b/', $archivo->getContents()))
            ->map(fn ($archivo) => $archivo->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $fuera, 'Usa los tokens de la paleta (brand, peach, sage, ink, line…).');
    }

    public function test_el_perfil_no_se_desborda_en_celular(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        // Sin columna explícita, la fila de pestañas ensanchaba la cuadrícula 120 px en 375 px.
        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('grid grid-cols-1 gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]', false)
            ->assertSee('card-boutique min-w-0 p-4', false);
    }

    public function test_las_acciones_de_paciente_son_comodas_al_tacto(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        \App\Models\Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678', 'email' => 'ana@example.test']);

        $this->actingAs($admin)
            ->get(route('admin.pacientes.index'))
            ->assertOk()
            ->assertSee('inline-flex min-h-11 items-center gap-1 rounded-xl bg-brand-50', false)
            ->assertSee('inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-line', false);
    }
}
