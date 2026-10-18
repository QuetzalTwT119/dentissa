<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\AgendaTelegramService;
use App\Services\CrearCitaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgendaTelegramServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_mismo_horario_no_se_registra_dos_veces(): void
    {
        $servicio = Servicio::create(['nombre' => 'Preventiva', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento->id);
        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }
        HorarioAtencion::where('dia_semana', $fecha->dayOfWeek)->update([
            'abierto' => true,
            'hora_apertura' => 8,
            'hora_cierre' => 18,
            'capacidad_simultanea' => 1,
        ]);

        $datos = [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '09:00',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '7555550203',
            'email' => 'ana.primera@example.test',
        ];
        $crear = app(CrearCitaService::class);

        $primera = $crear->crear($datos, 'publico');
        $segunda = $crear->crear([
            ...$datos,
            'nombre' => 'Beto',
            'email' => 'beto.segundo@example.test',
        ], 'telegram', 'telegram:slot-already-taken');

        $this->assertTrue($primera['creada']);
        $this->assertNull($segunda['cita']);
        $this->assertDatabaseCount('citas', 1);
    }

    public function test_guia_al_paciente_hasta_mostrar_horarios_reales(): void
    {
        $servicio = Servicio::create(['nombre' => 'Preventiva', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento->id);

        $fecha = Carbon::tomorrow();
        while ($fecha->dayOfWeek === 0) {
            $fecha->addDay();
        }
        HorarioAtencion::where('dia_semana', $fecha->dayOfWeek)->update([
            'abierto' => true,
            'hora_apertura' => 8,
            'hora_cierre' => 18,
            'capacidad_simultanea' => 1,
        ]);

        $agenda = app(AgendaTelegramService::class);

        $this->assertStringContainsString('¿qué tipo de atención necesitas?', mb_strtolower($agenda->responder(999, 'Quiero una cita')));
        $this->assertStringContainsString('¿Qué tratamiento necesitas?', $agenda->responder(999, 'Preventiva'));
        $this->assertSame('Limpieza Dental', $agenda->tecladoActual(999)['inline_keyboard'][0][0]['text']);
        $this->assertStringContainsString('calendario', mb_strtolower($agenda->responder(999, 'Limpieza Dental')));
        $botonFecha = collect($agenda->tecladoActual(999)['inline_keyboard'])
            ->flatten(1)
            ->first(fn (array $boton) => $boton['callback_data'] === 'agenda:fecha:'.$fecha->toDateString());
        $this->assertNotNull($botonFecha);
        $this->assertStringContainsString('Hay estos horarios', $agenda->responderSeleccion(
            999,
            $botonFecha['callback_data'],
            'cb-date-999',
        ));
        foreach ($agenda->tecladoActual(999)['inline_keyboard'] as $fila) {
            $this->assertTrue(array_is_list($fila), 'Cada fila del teclado debe serializarse como una lista JSON.');
        }
        $horario = $agenda->tecladoActual(999)['inline_keyboard'][0][0]['callback_data'];
        $hora = substr($horario, strlen('agenda:horario:'));
        $this->assertStringContainsString('sin apellidos', $agenda->responderSeleccion(999, $horario, 'callback-time-999'));
        $this->assertStringContainsString('apellidos', $agenda->responder(999, 'Ana'));
        $this->assertStringContainsString('número', $agenda->responder(999, 'Prueba'));
        $this->assertSame([
            'keyboard' => [[['text' => '📱 Compartir mi número', 'request_contact' => true]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
            'input_field_placeholder' => 'O escribe tu número',
        ], $agenda->tecladoActual(999));
        $this->assertStringContainsString('correo', $agenda->responder(999, '+52 755 555 0101'));
        $this->assertSame(['remove_keyboard' => true], $agenda->tecladoActual(999));
        $this->assertStringContainsString('motivo', $agenda->responder(999, 'ana.prueba@example.test'));
        $resumen = $agenda->responderSeleccion(999, 'agenda:motivo:omitir', 'callback-skip-999');
        $this->assertStringContainsString('Revisa los datos de tu cita', $resumen);
        $this->assertStringContainsString('Teléfono: •••• 0101', $resumen);
        $this->assertStringContainsString('Correo: a•••@example.test', $resumen);
        $this->assertStringNotContainsString('+52 755 555 0101', $resumen);
        $this->assertStringNotContainsString('ana.prueba@example.test', $resumen);

        config(['dentissa.whatsapp.phone_number_id' => null, 'dentissa.whatsapp.access_token' => null]);
        Mail::fake();
        $confirmacion = $agenda->responderSeleccion(999, 'agenda:confirmar:si', 'callback-confirm-999');

        $this->assertStringContainsString('tu cita quedó agendada', $confirmacion);
        $cita = Cita::with('paciente')->firstOrFail();
        $this->assertSame('ana.prueba@example.test', $cita->paciente->email);
        $this->assertSame('+52 755 555 0101', $cita->paciente->telefono);
        $this->assertSame($hora, $cita->fecha_hora->format('H:i'));
        $this->assertSame('telegram:callback-confirm-999', $cita->clave_idempotencia);
        $this->assertDatabaseMissing('logs_auditoria', [
            'tabla' => 'citas',
            'registro_id' => $cita->id,
            'accion' => 'cita.creada',
        ]);

        $reintento = app(CrearCitaService::class)->crear([
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => $hora,
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '+52 755 555 0101',
            'email' => 'ana.prueba@example.test',
        ], 'telegram', 'telegram:callback-confirm-999');

        $this->assertFalse($reintento['creada']);
        $this->assertSame($cita->id, $reintento['cita']->id);
        $this->assertDatabaseCount('citas', 1);
    }

    public function test_calendario_permite_avanzar_y_seleccionar_un_dia_del_mes_mostrado(): void
    {
        $servicio = Servicio::create(['nombre' => 'Preventiva', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento->id);
        $agenda = app(AgendaTelegramService::class);
        $agenda->iniciar(998);
        $agenda->responderSeleccion(998, 'agenda:servicio:'.$servicio->id);
        $agenda->responderSeleccion(998, 'agenda:tratamiento:'.$tratamiento->id);

        $respuesta = $agenda->responderSeleccion(998, 'agenda:calendario:+1', 'cb-next-month');
        $this->assertStringContainsString('Elige una fecha', $respuesta);

        $mesSiguiente = today()->addMonthNoOverflow()->startOfMonth();
        $fechaElegible = $mesSiguiente->copy()->day(1);
        while ($fechaElegible->isSunday()) {
            $fechaElegible->addDay();
        }
        HorarioAtencion::where('dia_semana', $fechaElegible->dayOfWeek)->update([
            'abierto' => true,
            'hora_apertura' => 8,
            'hora_cierre' => 18,
            'capacidad_simultanea' => 1,
        ]);
        $botonFecha = collect($agenda->tecladoActual(998)['inline_keyboard'])
            ->flatten(1)
            ->first(fn (array $boton) => $boton['callback_data'] === 'agenda:fecha:'.$fechaElegible->toDateString());
        $this->assertNotNull($botonFecha);
        $this->assertStringContainsString('Hay estos horarios', $agenda->responderSeleccion(
            998,
            $botonFecha['callback_data'],
            'cb-next-month-date',
        ));
    }
}
