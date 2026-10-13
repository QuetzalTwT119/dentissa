<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Exceptions\AuditoriaNoDisponible;
use App\Models\Cita;
use App\Models\HistorialClinico;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuditoriaAtomicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_reserva_publica_se_revierte_si_falla_la_auditoria(): void
    {
        $servicio = Servicio::create(['nombre' => 'Servicio QA', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento QA',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);
        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        $this->fallarAuditoria();

        $this->post(route('citas.publica.store'), [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '09:00',
            'nombre' => 'Paciente',
            'apellido' => 'QA',
            'telefono' => '5555555555',
            'email' => 'paciente-auditoria@example.test',
        ])
            ->assertStatus(503)
            ->assertSee('No se guardaron los cambios');

        $this->assertDatabaseCount('citas', 0);
        $this->assertDatabaseMissing('pacientes', ['email' => 'paciente-auditoria@example.test']);
    }

    public function test_la_cancelacion_de_cita_se_revierte_si_falla_la_auditoria(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'QA',
            'telefono' => '5555555555',
            'email' => 'paciente-cancelacion-auditoria@example.test',
        ]);
        $servicio = Servicio::create(['nombre' => 'Servicio QA', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento QA',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => Carbon::tomorrow()->setTime(10, 0),
            'duracion_estimada' => 30,
            'estado' => EstadoCita::Agendada,
        ]);

        $this->fallarAuditoria();

        $this->actingAs($admin)
            ->patch(route('citas.cancel', $cita), ['motivo_cancelacion' => 'Prueba QA'])
            ->assertStatus(503)
            ->assertSee('No se guardaron los cambios');

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => EstadoCita::Agendada->value,
            'motivo_cancelacion' => null,
        ]);
    }

    public function test_los_datos_oficiales_del_paciente_se_revierten_si_falla_la_auditoria(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'QA',
            'telefono' => '5555555555',
            'email' => 'paciente-auditoria@example.test',
        ]);

        $this->fallarAuditoria();

        $this->actingAs($admin)
            ->put(route('admin.pacientes.update', $paciente), [
                'curp' => 'GORP800101HDFRZL09',
                'rfc' => 'GORP800101AB1',
                'ine_clave' => 'ABC123456789012345',
                'tutor_id' => null,
            ])
            ->assertStatus(503)
            ->assertSee('No se guardaron los cambios');

        $this->assertDatabaseHas('pacientes', [
            'id' => $paciente->id,
            'curp' => null,
            'rfc' => null,
            'ine_clave' => null,
        ]);
    }

    public function test_el_historial_clinico_se_revierte_si_falla_la_auditoria(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'QA',
            'telefono' => '5555555555',
            'email' => 'paciente-historial-auditoria@example.test',
        ]);
        $historial = HistorialClinico::create([
            'id_paciente' => $paciente->id,
            'id_doctor' => $admin->id,
            'fecha_consulta' => '2026-10-07',
            'tipo_consulta' => 'primera_vez',
            'motivo_consulta' => 'Motivo original',
            'diagnostico' => 'Diagnóstico original',
        ]);

        $this->fallarAuditoria();

        $this->actingAs($admin)
            ->put(route('admin.pacientes.historial.update', [$paciente, $historial]), [
                'fecha_consulta' => '2026-10-08',
                'tipo_consulta' => 'seguimiento',
                'motivo_consulta' => 'Motivo cambiado',
                'diagnostico' => 'Diagnóstico cambiado',
                'tratamiento_realizado' => '',
                'medicamentos_recetados' => '',
                'observaciones' => '',
                'notas_doctor' => '',
            ])
            ->assertStatus(503)
            ->assertSee('No se guardaron los cambios');

        $this->assertDatabaseHas('historial_clinico', [
            'id' => $historial->id,
            'motivo_consulta' => 'Motivo original',
            'diagnostico' => 'Diagnóstico original',
        ]);
    }

    private function fallarAuditoria(): void
    {
        $this->mock(AuditoriaService::class, function ($mock): void {
            $mock->shouldReceive('registrar')
                ->once()
                ->andThrow(new AuditoriaNoDisponible('Fallo simulado de auditoría.'));
        });
    }
}
