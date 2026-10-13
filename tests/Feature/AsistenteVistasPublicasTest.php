<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenteVistasPublicasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_chat_esta_disponible_en_todas_las_vistas_publicas_de_atencion_al_paciente(): void
    {
        foreach ([
            'inicio' => url('/'),
            'agenda' => route('citas.publica.create'),
            'atencion urgente' => route('atencion.urgencia'),
            'lista de espera' => route('atencion.lista-espera'),
        ] as $vista => $url) {
            $respuesta = $this->get($url)->assertOk();

            $this->assertStringContainsString('Dentissa Asistente', $respuesta->getContent(), "Falta el asistente en la vista de {$vista}.");
            $this->assertSame(1, substr_count($respuesta->getContent(), 'id="consulta-inicio"'), "La vista de {$vista} debe incluir exactamente un chat.");
        }
    }
}
