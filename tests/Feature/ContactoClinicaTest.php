<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Mail\RecordatorioCita;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El teléfono y el WhatsApp de la clínica salen de config('dentissa.contacto').
 * Antes la portada tenía el número real escrito a mano y la página del
 * recordatorio mostraba el de ejemplo (+52 55 0000 0000).
 */
class ContactoClinicaTest extends TestCase
{
    use RefreshDatabase;

    private const VARIABLES = ['DENTISSA_TELEFONO', 'DENTISSA_WHATSAPP'];

    public function test_sin_variables_o_con_variables_vacias_usa_el_numero_de_la_clinica(): void
    {
        // null = variable ausente; '' = variable vacía, como en .env.example.
        foreach ([null, ''] as $valor) {
            $contacto = $this->configuracionCon($valor)['contacto'];

            $this->assertSame('755 102 0089', $contacto['telefono']);
            $this->assertSame('tel:+527551020089', $contacto['telefono_enlace']);
            $this->assertSame('527551020089', $contacto['whatsapp']);
        }
    }

    public function test_el_enlace_tel_respeta_un_numero_que_ya_trae_lada_internacional(): void
    {
        $this->assertSame('tel:+523312345678', $this->configuracionCon('+52 33 1234 5678')['contacto']['telefono_enlace']);
        $this->assertSame('tel:+523312345678', $this->configuracionCon('33 1234 5678')['contacto']['telefono_enlace']);
    }

    public function test_la_portada_y_la_pagina_del_recordatorio_muestran_el_mismo_numero(): void
    {
        config([
            'dentissa.contacto.telefono' => '33 1234 5678',
            'dentissa.contacto.telefono_enlace' => 'tel:+523312345678',
            'dentissa.contacto.whatsapp' => '523312345678',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="tel:+523312345678"', false)
            ->assertSee('33 1234 5678')
            ->assertSee('href="https://wa.me/523312345678"', false);

        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678', 'email' => 'ana@example.test']);
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            // Ya pasó (no admite cambios), pero su enlace sigue vigente: caduca un día
            // después de la cita. Con subDay() caducaba en el mismo segundo de la prueba.
            'fecha_hora' => now()->subHours(2),
            'duracion_estimada' => 60,
            'estado' => EstadoCita::Confirmada,
        ]);

        $this->get(RecordatorioCita::urlFirmada('citas.recordatorio.show', $cita, 'confirmar'))
            ->assertOk()
            ->assertSee('Esta cita ya no admite cambios.')
            ->assertSee('href="tel:+523312345678"', false)
            ->assertSee('33 1234 5678')
            ->assertDontSee('55 0000 0000');
    }

    /** Evalúa config/dentissa.php con DENTISSA_TELEFONO y DENTISSA_WHATSAPP fijados. */
    private function configuracionCon(?string $telefono): array
    {
        $respaldo = [];
        foreach (self::VARIABLES as $variable) {
            $respaldo[$variable] = [$_ENV[$variable] ?? null, $_SERVER[$variable] ?? null, getenv($variable)];
        }

        try {
            foreach (self::VARIABLES as $variable) {
                $valor = $variable === 'DENTISSA_TELEFONO' ? $telefono : null;
                if ($valor === null) {
                    unset($_ENV[$variable], $_SERVER[$variable]);
                    putenv($variable);
                } else {
                    $_ENV[$variable] = $_SERVER[$variable] = $valor;
                    putenv("{$variable}={$valor}");
                }
            }

            return require config_path('dentissa.php');
        } finally {
            foreach ($respaldo as $variable => [$env, $server, $put]) {
                if ($env === null) {
                    unset($_ENV[$variable]);
                } else {
                    $_ENV[$variable] = $env;
                }
                if ($server === null) {
                    unset($_SERVER[$variable]);
                } else {
                    $_SERVER[$variable] = $server;
                }
                $put === false ? putenv($variable) : putenv("{$variable}={$put}");
            }
        }
    }
}
