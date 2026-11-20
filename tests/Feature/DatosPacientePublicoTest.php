<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Paso "Datos" de la agenda pública (variante "Clásica"): el teléfono se
 * muestra con espacios, el navegador avisa antes de enviar y el servidor
 * sigue validando con las mismas reglas.
 */
class DatosPacientePublicoTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        $servicio = Servicio::firstOrCreate(['nombre' => 'Odontología General'], ['orden' => 1, 'activo' => true]);
        $tratamiento = Tratamiento::firstOrCreate(
            ['nombre' => 'Limpieza Dental'],
            ['duracion_estimada' => 60, 'categoria' => 'Preventiva', 'activo' => true],
        );
        $servicio->tratamientos()->syncWithoutDetaching([$tratamiento->id]);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        return $cambios + [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '10:00',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5512345678',
            'email' => 'ana.datos@example.test',
            'acepta_aviso' => '1',
        ];
    }

    public function test_un_telefono_con_espacios_se_guarda_solo_con_digitos(): void
    {
        $this->post(route('citas.publica.store'), $this->datos(['telefono' => '55 1234 5678']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('5512345678', Paciente::where('email', 'ana.datos@example.test')->value('telefono'));
    }

    public function test_solo_se_toleran_espacios_en_el_telefono(): void
    {
        foreach (['55-1234-5678', '+52 55 1234 5678', '(55) 1234 5678', '55 1234 567'] as $telefono) {
            $this->post(route('citas.publica.store'), $this->datos(['telefono' => $telefono]))
                ->assertSessionHasErrors('telefono');
        }
    }

    public function test_el_paso_de_datos_valida_en_el_navegador_y_recuerda_la_cita(): void
    {
        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('x-data="datosPaciente(', false)
            ->assertSee('x-on:submit="enviar($event)"', false)
            ->assertSee('novalidate', false)
            ->assertSee('Teléfono (WhatsApp)')
            ->assertSee('x-on:blur="alSalir(\'email\')"', false)
            // Recordatorio de la cita con "Cambiar" que regresa a fecha y hora.
            ->assertSee('@click="mover(\'atras\'); paso = 1"', false)
            ->assertSee('Cambiar');
    }

    public function test_si_el_servidor_rechaza_los_datos_los_errores_llegan_al_formulario(): void
    {
        $this->from(route('citas.publica.create'))
            ->post(route('citas.publica.store'), $this->datos(['nombre' => '', 'telefono' => '123', 'acepta_aviso' => null]))
            ->assertRedirect(route('citas.publica.create'));

        $html = $this->get(route('citas.publica.create'))->assertOk()->getContent();

        $this->assertStringContainsString('Revisa los 3 datos marcados para poder agendar tu cita.', $html);
        $this->assertStringContainsString('Para agendar necesitamos que aceptes el aviso de privacidad.', $html);
        // Lo que ya estaba bien se conserva.
        $this->assertStringContainsString('value="ana.datos@example.test"', $html);
    }
}
