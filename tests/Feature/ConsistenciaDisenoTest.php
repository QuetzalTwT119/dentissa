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

    public function test_las_confirmaciones_usan_el_dialogo_propio_y_no_el_del_navegador(): void
    {
        // Ninguna vista llama a confirm(), prompt() o alert() del navegador.
        $nativos = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($archivo) => str_ends_with($archivo->getFilename(), '.blade.php'))
            ->reject(fn ($archivo) => $archivo->getFilename() === 'dialogo-confirmar.blade.php')
            ->filter(fn ($archivo) => preg_match('/\b(confirm|prompt|alert)\(/', $archivo->getContents()))
            ->map(fn ($archivo) => $archivo->getRelativePathname())
            ->values()
            ->all();
        $this->assertSame([], $nativos, 'Usa data-confirmar="…" (components/dialogo-confirmar).');

        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        \App\Models\Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);

        $this->actingAs($admin)
            ->get(route('admin.servicios.index'))
            ->assertOk()
            ->assertSee('data-dialogo-confirmar', false)
            ->assertSee('data-confirmar-titulo="¿Eliminar el servicio «Odontología General»?"', false)
            ->assertSee('data-confirmar-boton="Sí, eliminar"', false);
    }

    public function test_crear_y_guardar_piden_confirmacion(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $servicio = \App\Models\Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);

        foreach (['admin.servicios.create', 'admin.tratamientos.create', 'admin.ofertas.create', 'admin.usuarios.create', 'citas.create'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk()->assertSee('data-confirmar-boton="Sí, crear"', false);
        }
        $this->actingAs($admin)->get(route('admin.servicios.edit', $servicio))->assertOk()
            ->assertSee('data-confirmar-titulo="¿Guardar los cambios del servicio?"', false);
    }

    public function test_galeria_y_casos_se_ocultan_del_menu_sin_borrarse_y_el_vacio_abarca_la_tabla(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $html = $this->actingAs($admin)->get(route('admin.ofertas.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('href="'.route('admin.galeria.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.casos.index').'"', $html);
        // Lista vacía: el mensaje abarca todas las columnas (antes quedaba descentrado).
        $this->assertStringContainsString('colspan="100"', $html);

        // Las rutas siguen funcionando y se reactivan con la variable.
        $this->actingAs($admin)->get(route('admin.galeria.index'))->assertOk();
        config(['dentissa.modulos.galeria' => true]);
        $this->actingAs($admin)->get(route('admin.ofertas.index'))->assertSee('href="'.route('admin.galeria.index').'"', false);
    }

    public function test_cada_lista_tiene_un_solo_boton_para_crear(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        foreach (['casos', 'galeria', 'ofertas', 'preguntas-frecuentes', 'servicios', 'testimonios', 'tratamientos', 'usuarios'] as $modulo) {
            $html = $this->actingAs($admin)->get(route("admin.{$modulo}.index"))->assertOk()->getContent();
            $crear = route("admin.{$modulo}.create");

            // El botón del encabezado; el estado vacío puede sumar uno solo si la lista no tiene registros.
            $this->assertLessThanOrEqual(2, substr_count($html, 'href="'.$crear.'"'), "{$modulo}: botones de crear repetidos.");
            $this->assertStringNotContainsString('href="'.$crear.'" class="hidden sm:inline-block"', $html, "{$modulo}: quedó la copia del botón en la tarjeta.");
        }
    }

    public function test_el_asistente_de_chat_solo_esta_en_las_paginas_publicas(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        // Es para pacientes: portada y agenda en línea.
        $this->get('/')->assertOk()->assertSee('asistente-flotante', false);
        $this->get(route('citas.publica.create'))->assertOk()->assertSee('asistente-flotante', false);

        // No en el panel del personal.
        foreach (['dashboard', 'citas.index', 'admin.servicios.index', 'admin.pacientes.index', 'notificaciones.index', 'profile.edit'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk()->assertDontSee('asistente-flotante', false);
        }
    }

    /**
     * Regla del 20/11/2026: el botón de guardar nunca se desactiva; en su lugar,
     * cada campo obligatorio lleva asterisco en su etiqueta y el formulario
     * explica qué significa.
     */
    public function test_los_campos_obligatorios_llevan_asterisco_y_el_formulario_lo_explica(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $servicio = \App\Models\Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);

        $paginas = [
            ...array_map(fn ($ruta) => route($ruta), array_diff(self::FORMULARIOS, ['notificaciones.index'])),
            route('admin.servicios.edit', $servicio),
            route('admin.usuarios.edit', $admin),
        ];

        foreach ($paginas as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            preg_match_all('/<(?:input|select|textarea)\b(?:->|=>|[^>])*>/s', $html, $campos);

            $obligatorios = 0;
            foreach ($campos[0] as $campo) {
                if (! preg_match('/\srequired(?:\s|>|=|\/)/', $campo) || ! preg_match('/\sid="([^"]+)"/', $campo, $id)) {
                    continue;
                }
                // Etiqueta invisible (formulario de un solo campo, como "subir foto de perfil"): sin asterisco.
                if (preg_match('/<label[^>]*for="'.preg_quote($id[1], '/').'"[^>]*class="[^"]*\bsr-only\b/', $html)) {
                    continue;
                }
                $obligatorios++;
                $this->assertMatchesRegularExpression(
                    '/<label[^>]*for="'.preg_quote($id[1], '/').'"[^>]*>(?:(?!<\/label>).)*\(obligatorio\)/s',
                    $html,
                    "{$url}: la etiqueta de \"{$id[1]}\" debe llevar el asterisco de obligatorio."
                );
            }

            if ($obligatorios > 0) {
                $this->assertStringContainsString('data-nota-obligatorios', $html, "{$url}: falta la nota \"Los campos con * son obligatorios\".");
            }
        }
    }
}
