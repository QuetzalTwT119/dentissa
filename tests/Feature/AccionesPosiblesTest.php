<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\Oferta;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA de acciones (22/11/2026): cada botón de una fila debe poder hacer lo que
 * promete. Antes había botones que el servidor rechazaba: ✓ Publicar en una
 * oferta de tratamiento inactivo, la papelera en servicios y tratamientos con
 * citas activas, y "Desactivar" en la propia cuenta (que daba una página de
 * error). Ahora en su lugar sale ⚠ con el motivo.
 */
class AccionesPosiblesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);
    }

    public function test_ofertas_que_no_se_pueden_publicar_no_ofrecen_el_boton(): void
    {
        $servicio = Servicio::create(['nombre' => 'Ortodoncia', 'activo' => true]);
        $inactivo = Tratamiento::create(['nombre' => 'Brackets', 'duracion_estimada' => 60, 'activo' => false]);
        $servicio->tratamientos()->attach($inactivo);
        $base = ['descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 10,
            'fecha_inicio' => now()->subWeek()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'estado' => 'inactiva'];
        $deInactivo = Oferta::create($base + ['titulo' => 'De tratamiento inactivo', 'id_tratamiento' => $inactivo->id]);
        $vencida = Oferta::create(['titulo' => 'Ya vencida', 'fecha_fin' => now()->subDay()->toDateString()] + $base);
        $buena = Oferta::create($base + ['titulo' => 'Se puede publicar']);

        $html = $this->actingAs($this->admin)->get(route('admin.ofertas.index'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-no-publicable'));
        $this->assertStringContainsString('Su tratamiento está inactivo.', $html);
        $this->assertStringContainsString('Ya venció.', $html);
        $this->assertStringNotContainsString('action="'.route('admin.ofertas.publicar', $deInactivo).'"', $html);
        $this->assertStringNotContainsString('action="'.route('admin.ofertas.publicar', $vencida).'"', $html);
        $this->assertStringContainsString('action="'.route('admin.ofertas.publicar', $buena).'"', $html);

        // El servidor usa la misma regla.
        $this->actingAs($this->admin)->patch(route('admin.ofertas.publicar', $deInactivo))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Su tratamiento está inactivo.'));
    }

    public function test_servicios_y_tratamientos_con_citas_activas_no_ofrecen_eliminar(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        $libre = Servicio::create(['nombre' => 'Sin citas', 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true]);
        $otro = Tratamiento::create(['nombre' => 'Sin citas tampoco', 'duracion_estimada' => 30, 'activo' => true]);
        $servicio->tratamientos()->attach($tratamiento);
        Cita::create(['id_paciente' => Paciente::create(['nombre' => 'Ana', 'apellido' => 'López', 'telefono' => '5550001111'])->id,
            'id_servicio' => $servicio->id, 'id_tratamiento' => $tratamiento->id, 'fecha_hora' => now()->addDay()->setTime(9, 0),
            'duracion_estimada' => 30, 'estado' => EstadoCita::Confirmada]);

        $html = $this->actingAs($this->admin)->get(route('admin.servicios.index'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'data-no-eliminable'));
        $this->assertStringContainsString('Tiene 1 cita activa.', $html);
        $this->assertStringContainsString('action="'.route('admin.servicios.destroy', $libre).'"', $html);
        $this->assertStringNotContainsString('action="'.route('admin.servicios.destroy', $servicio).'"', $html);

        $html = $this->actingAs($this->admin)->get(route('admin.tratamientos.index'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'data-no-eliminable'));
        $this->assertStringContainsString('action="'.route('admin.tratamientos.destroy', $otro).'"', $html);
        $this->assertStringNotContainsString('action="'.route('admin.tratamientos.destroy', $tratamiento).'"', $html);
    }

    public function test_la_propia_cuenta_no_ofrece_desactivarse_y_las_demas_usan_el_boton_global(): void
    {
        $otro = User::factory()->create(['rol' => RolUsuario::Asistente, 'activo' => true]);

        $html = $this->actingAs($this->admin)->get(route('admin.usuarios.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-cuenta-propia', $html);
        $this->assertStringContainsString('No puedes desactivarla ni cambiar tu rol', $html);
        $this->assertStringNotContainsString('action="'.route('admin.usuarios.activo.toggle', $this->admin).'"', $html);
        $this->assertStringContainsString('action="'.route('admin.usuarios.activo.toggle', $otro).'"', $html);
        // Botón redondo global (x-admin.boton-fila), no el cuadrado de antes.
        $this->assertStringNotContainsString('class="rounded-xl p-2 transition', $html);
    }

    /**
     * 22/11/2026 (encontrado por el usuario): el estado se veía de tres formas
     * (interruptor en Tratamientos, etiqueta en Servicios/Casos/Galería/
     * Preguntas, etiqueta + botón en Usuarios). Ahora todas usan el interruptor
     * global, que pide confirmación y queda en Control de actividades.
     */
    public function test_todo_lo_que_se_enciende_y_apaga_usa_el_mismo_interruptor(): void
    {
        $servicio = Servicio::create(['nombre' => 'Ortodoncia', 'orden' => 1, 'activo' => true]);
        $caso = \App\Models\Caso::create(['titulo' => 'Sonrisa nueva', 'orden' => 1, 'activo' => false]);
        $foto = \App\Models\Imagen::create(['titulo' => 'Recepción', 'url' => 'galeria/a.jpg', 'tipo' => \App\Enums\TipoImagen::Galeria, 'orden' => 1, 'activa' => true]);
        $pregunta = \App\Models\PreguntaFrecuente::create(['pregunta' => '¿Atienden sábados?', 'respuesta' => 'Sí, de 9 a 14 h.', 'orden' => 1, 'activa' => true]);
        $otro = User::factory()->create(['rol' => RolUsuario::Asistente, 'activo' => true]);

        $casos = [
            ['admin.servicios.index', 'admin.servicios.estado', $servicio, 'activo', 'servicio.estado_cambiado'],
            ['admin.casos.index', 'admin.casos.estado', $caso, 'activo', 'caso.estado_cambiado'],
            ['admin.galeria.index', 'admin.galeria.estado', $foto, 'activa', 'galeria.estado_cambiado'],
            ['admin.preguntas-frecuentes.index', 'admin.preguntas-frecuentes.estado', $pregunta, 'activa', 'faq.estado_cambiado'],
            ['admin.usuarios.index', 'admin.usuarios.activo.toggle', $otro, 'activo', 'usuario.desactivado'],
        ];

        foreach ($casos as [$lista, $ruta, $modelo, $campo, $accion]) {
            $html = $this->actingAs($this->admin)->get(route($lista))->assertOk()->getContent();
            $this->assertStringContainsString('data-interruptor-estado', $html, $lista);
            $this->assertStringContainsString('role="switch"', $html, $lista);
            $this->assertStringContainsString('action="'.route($ruta, $modelo).'"', $html, $lista);
            $this->assertStringContainsString('data-confirmar-titulo=', $html, $lista);

            $antes = (bool) $modelo->{$campo};
            $this->actingAs($this->admin)->from(route($lista))->patch(route($ruta, $modelo))
                ->assertRedirect(route($lista))->assertSessionHas('success');
            $this->assertSame(! $antes, (bool) $modelo->fresh()->{$campo}, $ruta);
            $this->assertDatabaseHas('logs_auditoria', ['accion' => $accion, 'registro_id' => $modelo->getKey()]);
        }

        // Usuarios: el botón aparte de Acciones ya no existe; la cuenta propia sale bloqueada.
        $html = $this->actingAs($this->admin)->get(route('admin.usuarios.index'))->getContent();
        $this->assertStringNotContainsString('aria-label="Desactivar a ', $html);
        $this->assertStringContainsString('data-interruptor-bloqueado', $html);

        // La asistente no puede cambiar estados del catálogo.
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente, 'activo' => true]);
        $this->actingAs($asistente)->patch(route('admin.servicios.estado', $servicio))->assertForbidden();
    }

    /** QA del panel (22/11/2026): Galería usaba enlaces de texto propios en lugar de las acciones globales. */
    public function test_galeria_usa_las_acciones_globales(): void
    {
        $imagen = \App\Models\Imagen::create(['titulo' => 'Recepción', 'url' => 'galeria/a.jpg', 'tipo' => \App\Enums\TipoImagen::Galeria, 'orden' => 1, 'activa' => true]);

        $this->actingAs($this->admin)->get(route('admin.galeria.index'))->assertOk()
            ->assertSee('aria-label="Editar la imagen «Recepción»"', false)
            ->assertSee('aria-label="Eliminar la imagen «Recepción»"', false)
            ->assertSee('action="'.route('admin.galeria.destroy', $imagen).'"', false);
    }

    /** QA del panel: "Ver cambios" de Control de actividades mostraba "null" en los campos vacíos. */
    public function test_control_de_actividades_no_muestra_null(): void
    {
        \App\Models\LogAuditoria::query()->create([
            'id_usuario' => $this->admin->id, 'accion' => 'oferta.actualizada', 'tabla' => 'ofertas', 'registro_id' => 1,
            'datos_anteriores' => ['imagen' => null, 'activa' => true], 'datos_nuevos' => ['imagen' => 'ofertas/a.jpg', 'activa' => false],
            'fecha' => now(),
        ]);

        $html = $this->actingAs($this->admin)->get(route('admin.auditoria.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('>null<', $html);
        // 22/11/2026: valores en palabras (DetalleBitacora), ya no rutas ni true/false.
        $this->assertStringContainsString('>Sin foto<', $html);
        $this->assertStringContainsString('>Foto subida<', $html);
    }
}
