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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        foreach (self::FORMULARIOS as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, '<h1'), "{$ruta} debe tener exactamente un <h1>.");
        }
    }

    public function test_los_campos_de_texto_y_listas_miden_al_menos_44_px(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        // Sin columna explícita, la fila de pestañas ensanchaba la cuadrícula 120 px en 375 px.
        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('grid grid-cols-1 gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]', false)
            ->assertSee('card-boutique min-w-0 p-4', false);
    }

    public function test_las_acciones_de_paciente_son_comodas_al_tacto(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        \App\Models\Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678', 'email' => 'ana@example.test']);

        $this->actingAs($admin)
            ->get(route('admin.pacientes.index'))
            ->assertOk()
            // Historial (📋, H-54) y Editar usan el mismo tamaño: 44 px en celular, compacto desde lg.
            ->assertSee('data-ver-historial', false)
            ->assertSee('grid h-11 w-11 place-items-center rounded-full', false);
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

        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $servicio = \App\Models\Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);

        foreach (['admin.servicios.create', 'admin.tratamientos.create', 'admin.ofertas.create', 'admin.usuarios.create', 'citas.create'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk()->assertSee('data-confirmar-boton="Sí, crear"', false);
        }
        $this->actingAs($admin)->get(route('admin.servicios.edit', $servicio))->assertOk()
            ->assertSee('data-confirmar-titulo="¿Guardar los cambios del servicio?"', false);
    }

    public function test_galeria_y_casos_se_ocultan_del_menu_sin_borrarse_y_el_vacio_abarca_la_tabla(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

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

    public function test_editar_y_eliminar_son_iguales_en_todas_las_listas_y_los_filtros_van_en_la_barra(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = \App\Models\Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678', 'email' => 'ana@example.test']);
        \App\Models\PreguntaFrecuente::create(['pregunta' => '¿Atienden niños?', 'respuesta' => 'Sí.', 'orden' => 1, 'activa' => true]);
        \App\Models\Testimonio::create(['id_paciente' => $paciente->id, 'calificacion' => 5, 'comentario' => 'Muy bien atendida.', 'estado' => 'pendiente']);

        foreach (['preguntas-frecuentes', 'testimonios'] as $modulo) {
            $html = $this->actingAs($admin)->get(route("admin.{$modulo}.index"))->assertOk()->getContent();
            // Mismo botón (componente compartido) para editar y eliminar.
            if ($modulo !== 'testimonios') { // los testimonios no se editan
                $this->assertStringContainsString('lg:h-9 lg:w-9 text-brand-600', $html, "{$modulo}: editar");
            }
            $this->assertStringContainsString('lg:h-9 lg:w-9 text-rose-600', $html, "{$modulo}: eliminar");
            $this->assertStringContainsString('data-confirmar-boton="Sí, eliminar"', $html);
        }

        // Un solo botón de eliminar por fila (antes Testimonios mostraba dos).
        $html = $this->actingAs($admin)->get(route('admin.testimonios.index'))->getContent();
        $this->assertSame(1, substr_count($html, 'aria-label="Eliminar el testimonio'));
        $this->assertStringContainsString('Aprobar testimonio de Ana', $html);
        // La clínica modera, no escribe ni cambia testimonios.
        $this->assertStringNotContainsString('aria-label="Editar el testimonio', $html);
        $this->assertStringNotContainsString('Nuevo testimonio', $html);
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.testimonios.edit'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.testimonios.create'));

        // El filtro de testimonios vive dentro de la barra (sin franja aparte).
        $html = $this->actingAs($admin)->get(route('admin.testimonios.index'))->getContent();
        $barra = strpos($html, 'data-paginacion="arriba"');
        $this->assertGreaterThan($barra, strpos($html, 'id="filtro_estado"'));
        $this->assertStringNotContainsString('Filtrar por estado:', $html);
    }

    public function test_cada_lista_tiene_un_solo_boton_para_crear(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        foreach (['casos', 'galeria', 'ofertas', 'preguntas-frecuentes', 'servicios', 'tratamientos', 'usuarios'] as $modulo) {
            $html = $this->actingAs($admin)->get(route("admin.{$modulo}.index"))->assertOk()->getContent();
            $crear = route("admin.{$modulo}.create");

            // El botón del encabezado; el estado vacío puede sumar uno solo si la lista no tiene registros.
            $this->assertLessThanOrEqual(2, substr_count($html, 'href="'.$crear.'"'), "{$modulo}: botones de crear repetidos.");
            $this->assertStringNotContainsString('href="'.$crear.'" class="hidden sm:inline-block"', $html, "{$modulo}: quedó la copia del botón en la tarjeta.");
        }
    }

    public function test_el_asistente_de_chat_solo_esta_en_las_paginas_publicas(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $servicio = \App\Models\Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);

        $paginas = [
            ...array_map(fn ($ruta) => route($ruta), array_diff(self::FORMULARIOS, ['notificaciones.index'])),
            route('admin.servicios.edit', $servicio),
            // Editar usuario ya no existe (22/11/2026): Usuarios solo crea, desactiva y elimina.
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

    /**
     * Revisión del 20/11/2026: en PC (ventana real de 1536×748) varias pantallas
     * obligaban a desplazar la página. Regla: todo el panel usa el marco ajustado
     * a la ventana (app.css, "Panel ajustado"); las listas desplazan solo su tabla
     * y los formularios van en columnas con los botones siempre a la vista.
     */
    public function test_todas_las_pantallas_del_panel_se_ajustan_a_la_ventana(): void
    {
        $sinMarco = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($archivo) => str_ends_with($archivo->getFilename(), '.blade.php'))
            ->filter(fn ($archivo) => str_contains($archivo->getContents(), '<x-app-layout>'))
            ->reject(fn ($archivo) => str_contains($archivo->getContents(), 'panel-contenido'))
            ->reject(fn ($archivo) => str_contains($archivo->getContents(), "@include('admin.tratamientos._formulario'"))
            ->map(fn ($archivo) => $archivo->getRelativePathname())
            ->values()
            ->all();
        $this->assertSame([], $sinMarco, 'Pantallas del panel sin .panel-contenido');

        $this->assertStringContainsString('panel-marco', File::get(resource_path('views/layouts/app.blade.php')));
        $this->assertStringContainsString('tabla-panel', File::get(resource_path('views/components/admin/table.blade.php')));

        $css = File::get(resource_path('css/app.css'));
        foreach (['.panel-marco {', '.panel-contenido {', '.formulario-panel {', 'position: sticky;'] as $regla) {
            $this->assertStringContainsString($regla, $css);
        }
    }

    /**
     * 23/11/2026: el botón global "← Volver a …" (components/volver) va solo en las
     * pantallas que se abren desde otra y NO tienen "Cancelar". 24/11/2026: en los
     * formularios "Cancelar" ya regresa, así que ahí no se repite.
     */
    public function test_las_pantallas_hijas_tienen_el_boton_global_de_volver(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = \App\Models\Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678']);

        // Sin "Cancelar": llevan Volver.
        foreach ([
            route('dos-pasos.show') => 'Volver a Mi perfil',
            route('admin.pacientes.historial.index', $paciente) => 'Volver a Pacientes',
        ] as $url => $texto) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee('data-volver', false)->assertSee($texto);
        }

        // Formularios con "Cancelar": no repiten el regreso.
        foreach ([
            route('admin.usuarios.create'),
            route('admin.ofertas.create'),
            route('admin.preguntas-frecuentes.create'),
            route('admin.casos.create'),
            route('admin.servicios.create'),
            route('admin.tratamientos.create'),
            route('citas.create'),
            route('admin.pacientes.edit', $paciente),
            route('admin.pacientes.historial.create', $paciente),
            route('admin.pacientes.historia.edit', $paciente),
            route('admin.pacientes.exploracion.create', $paciente),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee('Cancelar')->assertDontSee('data-volver', false);
        }

        // El componente es uno solo; nadie dibuja su propia flecha de regreso.
        $flechas = collect(File::allFiles(resource_path('views/admin')))
            ->filter(fn ($f) => str_contains($f->getContents(), 'M10 19l-7-7m0 0l7-7m-7 7h18'))
            ->map(fn ($f) => $f->getRelativePathname())->values()->all();
        $this->assertSame([], $flechas, 'Flechas de regreso hechas a mano');
    }

    public function test_los_formularios_del_panel_van_en_columnas(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        foreach (['admin.usuarios.create', 'admin.ofertas.create', 'admin.preguntas-frecuentes.create', 'citas.create'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk()->assertSee('formulario-panel', false);
        }
        $this->actingAs($admin)->get(route('citas.create'))->assertSee('col-fecha', false);
        // Perfil: una sección a la vez (pestañas) en lugar de una página larga.
        $this->actingAs($admin)->get(route('profile.edit'))->assertSee("seccion === 'password'", false);
    }

    public function test_reportes_filtra_al_cambiar_la_fecha_y_descarga_desde_la_tarjeta(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $html = $this->actingAs($admin)->get(route('admin.reportes.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('>Filtrar</button>', $html);
        $this->assertStringContainsString('$el.form.requestSubmit()', $html);
        // Las descargas van dentro del formulario del filtro (respetan el rango elegido).
        // Las descargas van en la misma tarjeta del filtro, a la derecha.
        $tarjeta = str($html)->after('data-filtro-barra')->before('<!-- Tarjetas de Métricas');
        $this->assertStringContainsString('Descargar PDF', $tarjeta);
        $this->assertStringContainsString('Descargar Excel', $tarjeta);
        // La flecha de las listas desplegables ya no tapa el texto.
        $this->assertStringContainsString('select:not([multiple]):not([size])', File::get(resource_path('css/app.css')));
    }

    /** 21/11/2026: cada pantalla tenía su propio diseño de filtros; ahora todas usan components/filtro. */
    public function test_todos_los_filtros_usan_el_componente_global(): void
    {
        $propios = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($a) => str_ends_with($a->getFilename(), '.blade.php'))
            ->reject(fn ($a) => str_contains($a->getPathname(), 'components'))
            ->filter(fn ($a) => str_contains($a->getContents(), 'method="GET"'))
            ->map(fn ($a) => $a->getRelativePathname())
            ->values()
            ->all();
        $this->assertSame([], $propios, 'Formularios de filtro hechos a mano (usa <x-filtro.barra>)');

        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        foreach (['admin.usuarios.index', 'admin.mensajes.index', 'admin.testimonios.index', 'admin.pacientes.index', 'admin.auditoria.index', 'citas.index', 'admin.reportes.index'] as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();
            $this->assertStringContainsString('data-filtro-barra', $html, $ruta);
            $this->assertStringNotContainsString('>Filtrar</button>', $html, $ruta);
        }
    }

    /**
     * 21/11/2026: encabezados, tarjetas, campos largos, listas, botones y chips
     * estaban hechos a mano con diseños distintos. En las pantallas del panel
     * se usan los componentes globales.
     */
    public function test_las_piezas_del_panel_usan_los_componentes_globales(): void
    {
        $panel = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($a) => str_ends_with($a->getFilename(), '.blade.php'))
            ->filter(fn ($a) => preg_match('#[\\\\/](admin|citas|asistente-agenda|profile|notificaciones)[\\\\/]#', $a->getPathname()))
            ->mapWithKeys(fn ($a) => [str_replace('\\', '/', $a->getRelativePathname()) => $a->getContents()]);

        // Encabezado: x-admin.header (salvo el formulario de servicio —Nuevo y Editar— y tratamiento, con ruta y Guardar arriba).
        $encabezados = $panel->filter(fn ($c, $r) => str_contains($c, '<x-slot name="header">') && ! str_contains($c, '<x-admin.header')
            && ! str_contains($r, 'servicios/_formulario') && ! str_contains($r, 'tratamientos/')
            // Cuestionario de historia clínica: migas + título propio (22/11/2026).
            && ! str_contains($r, 'historia-clinica/') && ! str_contains($r, 'exploracion-clinica/'))->keys()->all();
        $this->assertSame([], $encabezados, 'Encabezados hechos a mano');

        // Campos largos y listas de formulario: x-textarea / x-select.
        $this->assertSame([], $panel->filter(fn ($c) => str_contains($c, '<textarea'))->keys()->all(), 'textarea a mano');
        $this->assertSame([], $panel->filter(fn ($c) => str_contains($c, '<select'))->keys()->all(), 'select a mano');

        // Botón principal: x-primary-button o .btn-brand, no la cadena de clases copiada.
        $this->assertSame([], $panel->filter(fn ($c) => str_contains($c, 'rounded-full bg-brand-600 px-5 py-2.5'))->keys()->all(), 'botón rosa a mano');

        // Chips: x-chip.
        $this->assertSame([], $panel->filter(fn ($c) => preg_match('/<span class="inline-flex items-center (gap-1\.5 )?rounded-full[^"]*px-2\.5 py-0\.5 text-xs font-semibold/', $c))->keys()->all(), 'chip a mano');

        // La tarjeta recorta sin crear contenedor de desplazamiento (la barra de botones sigue fija).
        $this->assertStringContainsString('overflow-clip', File::get(resource_path('views/components/admin/card.blade.php')));
    }

    /**
     * 21/11/2026: aprobar/rechazar testimonios, confirmar/iniciar/completar citas,
     * marcar leído, mover servicios, etc. se hacían de un clic. Regla: todo
     * formulario del panel que cambia algo pide confirmación con el diálogo del
     * sistema (data-confirmar → components/dialogo-confirmar).
     */
    public function test_todo_cambio_del_panel_pide_confirmacion(): void
    {
        // Formularios que no cambian datos o donde escribir el código ya es la confirmación.
        $excepciones = ['asistente-agenda.proponer', 'dos-pasos.confirmar', 'verification.send'];

        $sinConfirmar = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($a) => str_ends_with($a->getFilename(), '.blade.php'))
            ->filter(fn ($a) => preg_match('#[\\\\/](admin|citas|asistente-agenda|profile|notificaciones)[\\\\/]#', $a->getPathname()))
            ->flatMap(function ($archivo) {
                // La etiqueta <form …> completa; "->" de Blade no la corta.
                preg_match_all('/<form\b(?:->|[^>])*>/is', $archivo->getContents(), $etiquetas);

                return collect($etiquetas[0])
                    ->filter(fn ($form) => preg_match('/method="post"/i', $form) && ! str_contains($form, 'data-confirmar'))
                    ->map(fn ($form) => str_replace('\\', '/', $archivo->getRelativePathname()).' → '.(preg_match("/route\\('([^']+)/", $form, $r) ? $r[1] : $form));
            })
            ->reject(fn ($linea) => collect($excepciones)->contains(fn ($ruta) => str_ends_with($linea, $ruta)))
            ->values()
            ->all();

        $this->assertSame([], $sinConfirmar, 'Formularios que cambian datos sin data-confirmar (usa <x-admin.boton-fila> en filas)');

        // El botón de fila global siempre lleva la confirmación.
        $this->assertStringContainsString('data-confirmar="{{ $mensaje }}"', File::get(resource_path('views/components/admin/boton-fila.blade.php')));

        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $this->actingAs($admin)->get(route('admin.mensajes.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.servicios.index'))->assertOk();
    }
}
