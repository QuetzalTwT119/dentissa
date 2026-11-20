<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CitaPublicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_horarios_se_calculan_con_la_hora_local_de_la_clinica(): void
    {
        $this->assertSame('America/Mexico_City', config('app.timezone'));

        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00', 'America/Mexico_City'));

        try {
            $tratamiento = Tratamiento::create([
                'nombre' => 'Limpieza Dental',
                'duracion_estimada' => 60,
                'categoria' => 'Preventiva',
                'activo' => true,
            ]);

            $horarios = app(DisponibilidadService::class)
                ->horariosDisponibles('2026-10-17', $tratamiento);

            $this->assertContains('10:30', $horarios);
            $this->assertContains('13:00', $horarios);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_un_paciente_puede_agendar_una_cita_desde_el_formulario_publico(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('Odontología General')
            ->assertSee('Agendar cita');

        $respuesta = $this->post(route('citas.publica.store'), [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '09:00',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5555555555',
            'email' => 'ana.publica@example.test',
            'acepta_aviso' => '1',
            'motivo' => 'Consulta de prueba',
        ]);

        $respuesta
            ->assertRedirect(route('citas.publica.create'))
            ->assertSessionHas('success');

        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('¡Listo! Tu cita quedó confirmada. Te esperamos.')
            ->assertSee('Limpieza Dental')
            ->assertSee(ucfirst($fecha->copy()->locale('es')->translatedFormat('l j \d\e F \d\e Y')).' · 09:00 hrs')
            ->assertDontSee('Te enviaremos un recordatorio')
            ->assertSee('Volver al inicio');

        $paciente = Paciente::where('email', 'ana.publica@example.test')->firstOrFail();
        $this->assertSame('5555555555', $paciente->telefono);
        $cita = Cita::where('id_paciente', $paciente->id)->firstOrFail();

        $this->assertSame(EstadoCita::Confirmada, $cita->estado);
        $this->assertNull($cita->confirmada_por);
        $this->assertNotNull($cita->fecha_confirmacion);
        $this->assertSame(
            $fecha->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
            $cita->fecha_hora->format('Y-m-d H:i:s'),
        );
        $this->assertDatabaseHas('notificaciones', [
            'id_paciente' => $paciente->id,
            'id_cita' => $cita->id,
            'canal' => 'cita_confirmada',
        ]);

        $this->assertDatabaseMissing('logs_auditoria', [
            'tabla' => 'citas',
            'registro_id' => $cita->id,
            'accion' => 'cita.creada',
        ]);
    }

    public function test_el_formulario_publico_etiqueta_los_datos_requeridos_y_aclara_el_paso_de_resumen(): void
    {
        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('for="nombre"', false)
            ->assertSee('for="apellido"', false)
            ->assertSee('for="telefono"', false)
            ->assertSee('for="email"', false)
            ->assertDontSee('for="motivo"', false) // se platica en la consulta
            ->assertSee('name="nombre" autocomplete="given-name" inputmode="text" maxlength="100" required', false)
            ->assertSee('name="apellido" autocomplete="family-name" inputmode="text" maxlength="100" required', false)
            ->assertSee('name="telefono" autocomplete="tel-national" inputmode="numeric" maxlength="12" required', false)
            ->assertSee('placeholder="755 123 4567"', false)
            ->assertSee('name="email" autocomplete="email" inputmode="email" maxlength="100"', false)
            ->assertDontSee('name="email" autocomplete="email" inputmode="email" maxlength="100" required', false) // correo opcional
            ->assertSee('Resumen de la cita') // tarjeta lateral
            ->assertSee('Elegir hora')
            ->assertSee('Continuar a mis datos');
    }

    public function test_la_agenda_publica_rechaza_datos_de_contacto_vacios_sin_crear_registros(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        $this->post(route('citas.publica.store'), [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '09:00',
        ])
            ->assertSessionHasErrors(['nombre', 'apellido', 'telefono'])
            ->assertSessionDoesntHaveErrors('email'); // opcional desde el 20/11/2026

        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_sin_correo_se_agenda_y_el_celular_identifica_al_paciente(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'categoria' => 'Preventiva', 'activo' => true]);
        $servicio->tratamientos()->attach($tratamiento);
        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }
        $datos = fn (string $hora, string $email = '') => [
            'id_servicio' => $servicio->id, 'id_tratamiento' => $tratamiento->id, 'fecha' => $fecha->toDateString(), 'hora' => $hora,
            'nombre' => 'Luis', 'apellido' => 'Sincorreo', 'telefono' => '55 4444 3333', 'email' => $email, 'acepta_aviso' => '1',
        ];

        $this->post(route('citas.publica.store'), $datos('09:00'))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertNull(Paciente::firstOrFail()->email);

        // Segunda cita con el mismo celular: mismo paciente; si ahora da correo, se le guarda.
        $this->post(route('citas.publica.store'), $datos('11:00', 'luis@example.test'))->assertSessionHasNoErrors();
        $this->assertSame(1, Paciente::count());
        $this->assertSame('luis@example.test', Paciente::firstOrFail()->email);
        $this->assertSame(2, Cita::count());
    }

    public function test_las_horas_van_en_cuadricula_y_se_pueden_deseleccionar(): void
    {
        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('grid-cols-3 sm:grid-cols-5 lg:grid-cols-6', false)
            ->assertSee('Elegir hora')
            ->assertSee('hora = hora === horario ? null : horario', false)
            ->assertDontSee('max-h-[23.5rem]', false);
    }

    public function test_la_agenda_publica_limita_los_envios_repetidos_desde_la_misma_ip(): void
    {
        $datosIncompletos = ['hora' => '09:00'];
        $ipAbusiva = ['REMOTE_ADDR' => '203.0.113.10'];

        // Los primeros cinco envíos por minuto se procesan (aquí fallan por validación).
        for ($intento = 1; $intento <= 5; $intento++) {
            $this->withServerVariables($ipAbusiva)
                ->post(route('citas.publica.store'), $datosIncompletos)
                ->assertSessionHasErrors('nombre')
                ->assertSessionDoesntHaveErrors('envios');
        }

        // El sexto se bloquea con un mensaje amable y conserva lo capturado.
        $this->withServerVariables($ipAbusiva)
            ->post(route('citas.publica.store'), $datosIncompletos)
            ->assertRedirect(route('citas.publica.create'))
            ->assertSessionHasErrors('envios')
            ->assertSessionHasInput('hora', '09:00');

        // Al volver al formulario, el paciente ve el aviso en el paso de sus datos.
        $this->withServerVariables($ipAbusiva)
            ->followingRedirects()
            ->post(route('citas.publica.store'), $datosIncompletos)
            ->assertOk()
            ->assertSee('Recibimos varios intentos seguidos. Espera un minuto e inténtalo de nuevo.');

        // Otra IP no queda afectada por el bloqueo anterior.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->post(route('citas.publica.store'), $datosIncompletos)
            ->assertSessionDoesntHaveErrors('envios');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_la_agenda_publica_rechaza_telefonos_con_longitud_o_caracteres_invalidos(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        // Esta prueba revisa la validación del teléfono, no el límite de envíos.
        $this->withoutMiddleware(ThrottleRequests::class);

        foreach (['123456789', '12345678901', '521234567890', '+5555555555', 'abc1234567', '7551131833333333333'] as $telefono) {
            $this->post(route('citas.publica.store'), [
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha' => $fecha->toDateString(),
                'hora' => '09:00',
                'nombre' => 'Ana',
                'apellido' => 'Prueba',
                'telefono' => $telefono,
                'email' => 'ana.publica@example.test',
                'acepta_aviso' => '1',
            ])->assertSessionHasErrors('telefono');
        }

        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_el_formulario_publico_muestra_el_progreso_y_limita_el_telefono_a_mexico(): void
    {
        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('Tu cita, paso a paso')
            ->assertSee('aria-label="Volver al inicio"', false)
            ->assertSee('inputmode="numeric" maxlength="12"', false)
            // "55 1234 5678" mientras se escribe (datosPaciente en app.js); el servidor quita los espacios.
            ->assertSee('escribirTelefono($event)', false);
    }

    public function test_la_agenda_publica_rechaza_telefonos_que_no_sean_de_diez_digitos(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        foreach (['123456789', '12345678901', '521234567890', '+5555555555', 'abc1234567'] as $telefono) {
            $this->post(route('citas.publica.store'), [
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha' => $fecha->toDateString(),
                'hora' => '09:00',
                'nombre' => 'Ana',
                'apellido' => 'Prueba',
                'telefono' => $telefono,
                'email' => 'ana.publica@example.test',
                'acepta_aviso' => '1',
            ])->assertSessionHasErrors('telefono');
        }

        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_la_consulta_publica_no_puede_excluir_una_cita_existente_del_calculo_de_horarios(): void
    {
        $cita = $this->crearCitaExistente();

        $this->getJson(route('disponibilidad.index', [
            'tratamiento_id' => $cita->id_tratamiento,
            'fecha' => $cita->fecha_hora->toDateString(),
            'exclude_cita_id' => $cita->id,
        ]))->assertForbidden();
    }

    public function test_el_personal_puede_excluir_la_cita_que_esta_reagendando_del_calculo_de_horarios(): void
    {
        $cita = $this->crearCitaExistente();
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->getJson(route('disponibilidad.index', [
                'tratamiento_id' => $cita->id_tratamiento,
                'fecha' => $cita->fecha_hora->toDateString(),
                'exclude_cita_id' => $cita->id,
            ]))
            ->assertOk()
            ->assertJsonStructure(['horarios']);
    }

    private function crearCitaExistente(): Cita
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);
        $paciente = Paciente::create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5512345678',
            'email' => fake()->unique()->safeEmail(),
        ]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => Carbon::tomorrow()->setTime(10, 0),
            'duracion_estimada' => $tratamiento->duracion_estimada,
        ]);
    }

    public function test_el_componente_alpine_de_la_agenda_llega_completo_al_navegador(): void
    {
        $html = $this->get(route('citas.publica.create'))->assertOk()->getContent();

        // Si una comilla doble se cuela en el JavaScript de x-data, el atributo
        // se corta y el resto del código aparece como texto en la página.
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        $componente = (new \DOMXPath($dom))->query('//*[@x-data and contains(@x-data, "pasos")]')->item(0);

        $this->assertNotNull($componente, 'No se encontró el componente de la agenda.');
        $codigo = $componente->getAttribute('x-data');
        foreach (['get fechaLegible()', 'siguiente()', 'async cargarHorarios()'] as $fragmento) {
            $this->assertStringContainsString($fragmento, $codigo);
        }
        $this->assertStringNotContainsString('cargarHorariosDisponibles', $this->textoVisible($dom));
    }

    private function textoVisible(\DOMDocument $dom): string
    {
        // Texto de la página sin scripts ni atributos: lo que el paciente lee.
        foreach (iterator_to_array($dom->getElementsByTagName('script')) as $script) {
            $script->parentNode->removeChild($script);
        }

        return $dom->textContent;
    }

    public function test_el_calendario_usa_la_fecha_de_la_clinica_y_el_resumen_una_fecha_legible(): void
    {
        // 23:30 en Ciudad de México ya es el día siguiente en UTC; el mínimo debe seguir siendo hoy.
        Carbon::setTestNow(Carbon::parse('2030-01-09 23:30:00', 'America/Mexico_City'));

        try {
            $this->get(route('citas.publica.create'))
                ->assertOk()
                ->assertSee("hoy: '2030-01-09'", false)
                ->assertDontSee('toISOString', false)
                ->assertSee('get fechaLegible()', false)
                ->assertSee('x-text="fechaLegible || \'Pendiente\'"', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_la_agenda_publica_muestra_el_horario_configurado_y_no_un_texto_fijo(): void
    {
        HorarioAtencion::query()->update(['abierto' => false]);
        HorarioAtencion::whereIn('dia_semana', [1, 2, 3, 4, 5])->update(['abierto' => true, 'hora_apertura' => 8, 'hora_cierre' => 18]);
        HorarioAtencion::where('dia_semana', 6)->update(['abierto' => true, 'hora_apertura' => 9, 'hora_cierre' => 14]);

        $this->get(route('citas.publica.create'))
            ->assertOk()
            // Una línea por grupo en el recuadro "Horario del consultorio" del paso Día.
            ->assertSee('Horario del consultorio')
            ->assertSee('Lunes a viernes de 8:00 a 18:00 hrs')
            ->assertSee('Sábado de 9:00 a 14:00 hrs')
            ->assertDontSee('9:00 a 19:00');

        HorarioAtencion::where('dia_semana', 3)->update(['abierto' => false]);
        $this->assertSame(
            'Lunes a martes de 8:00 a 18:00 hrs • Jueves a viernes de 8:00 a 18:00 hrs • Sábado de 9:00 a 14:00 hrs.',
            HorarioAtencion::resumenSemanal(),
        );

        HorarioAtencion::query()->update(['abierto' => false]);
        $this->assertSame('Consulta la disponibilidad al elegir el día.', HorarioAtencion::resumenSemanal());
    }
}
