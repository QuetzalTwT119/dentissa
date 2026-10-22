<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Paciente;
use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TelegramAsistenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_pide_aclaracion_si_dos_faqs_son_igual_de_probables(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');

        PreguntaFrecuente::create([
            'pregunta' => '¿Qué horario hay para limpieza dental?',
            'respuesta' => 'Respuesta del horario.',
            'activa' => true,
        ]);
        PreguntaFrecuente::create([
            'pregunta' => '¿Qué costo tiene limpieza dental?',
            'respuesta' => 'Respuesta del costo.',
            'activa' => true,
        ]);

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [[
                    'update_id' => 1,
                    'message' => [
                        'chat' => ['id' => 123, 'type' => 'private'],
                        'text' => '¿Horario o costo de limpieza dental?',
                    ],
                ]],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 2],
            ]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])
            ->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === 123
            && str_starts_with($request['text'], 'No pude identificar con seguridad'));
    }

    public function test_comando_ayuda_muestra_como_usar_el_bot(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [[
                    'update_id' => 2,
                    'message' => [
                        'chat' => ['id' => 123, 'type' => 'private'],
                        'text' => '/ayuda',
                    ],
                ]],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 3],
            ]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && str_contains($request['text'], '/agendar')
            && str_contains($request['text'], '/cancelar'));
    }

    public function test_start_limpia_el_borrador_anterior_y_no_muestra_su_teclado(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        Cache::put('dentissa.telegram.agenda.123', [
            'paso' => 'horario',
            'servicio_id' => 1,
            'tratamiento_id' => 1,
            'fecha' => '2026-10-20',
        ]);

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [[
                    'update_id' => 40,
                    'message' => [
                        'chat' => ['id' => 123, 'type' => 'private'],
                        'text' => '/start',
                    ],
                ]],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 401]]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        $this->assertFalse(Cache::has('dentissa.telegram.agenda.123'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === 123
            && $request['reply_markup'] === ['remove_keyboard' => true]);
    }

    public function test_comando_agendar_inicia_el_flujo_de_horarios(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [[
                    'update_id' => 3,
                    'message' => [
                        'chat' => ['id' => 123, 'type' => 'private'],
                        'text' => '/agendar',
                    ],
                ]],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 4],
            ]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && str_contains(mb_strtolower($request['text']), '¿qué tipo de atención necesitas?')
            && str_contains($request['text'], 'Elige una opción:')
            && $request['reply_markup']['inline_keyboard'][0][0]['text'] === 'Odontología General'
            && str_starts_with($request['reply_markup']['inline_keyboard'][0][0]['callback_data'], 'agenda:servicio:'));
    }

    public function test_acepta_el_contacto_compartido_por_el_propio_usuario_y_quita_el_teclado(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        Cache::put('dentissa.telegram.agenda.123', [
            'paso' => 'telefono',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
        ]);

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [[
                    'update_id' => 8,
                    'message' => [
                        'chat' => ['id' => 123, 'type' => 'private'],
                        'from' => ['id' => 123],
                        'contact' => [
                            'user_id' => 123,
                            'phone_number' => '+52 755 555 0101',
                        ],
                    ],
                ]],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 9],
            ]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        $estado = Cache::get('dentissa.telegram.agenda.123');
        $this->assertSame('email', $estado['paso']);
        $this->assertSame('+52 755 555 0101', $estado['telefono']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && str_contains($request['text'], 'correo electrónico')
            && $request['reply_markup'] === ['remove_keyboard' => true]);
    }

    public function test_seleccionar_servicio_muestra_botones_de_tratamientos(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento->id);

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 4,
                        'message' => [
                            'chat' => ['id' => 123, 'type' => 'private'],
                            'text' => '/agendar',
                        ],
                    ],
                    [
                        'update_id' => 5,
                        'callback_query' => [
                            'id' => 'callback-1',
                            'data' => 'agenda:servicio:'.$servicio->id,
                            'message' => [
                                'message_id' => 11,
                                'chat' => ['id' => 123, 'type' => 'private'],
                            ],
                        ],
                    ],
                ],
            ]),
            'https://api.telegram.org/*/answerCallbackQuery' => Http::response([
                'ok' => true,
                'result' => true,
            ]),
            'https://api.telegram.org/*/editMessageReplyMarkup' => Http::response([
                'ok' => true,
                'result' => true,
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 6],
            ]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && str_contains($request['text'], '¿Qué tratamiento necesitas?')
            && $request['reply_markup']['inline_keyboard'][0][0]['text'] === 'Limpieza Dental'
            && $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === 'agenda:tratamiento:'.$tratamiento->id);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/answerCallbackQuery')
            && $request['callback_query_id'] === 'callback-1');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/editMessageReplyMarkup')
            && $request['message_id'] === 11
            && $request['reply_markup']['inline_keyboard'] === []);
    }

    public function test_navegar_calendario_edita_el_mismo_mensaje(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento->id);

        $chat = ['id' => 345, 'type' => 'private'];
        $callback = fn (int $updateId, string $queryId, string $data, int $messageId) => [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => $queryId,
                'data' => $data,
                'message' => ['message_id' => $messageId, 'chat' => $chat],
            ],
        ];

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [
                    ['update_id' => 20, 'message' => ['chat' => $chat, 'text' => '/agendar']],
                    $callback(21, 'cb-service-calendar', 'agenda:servicio:'.$servicio->id, 201),
                    $callback(22, 'cb-treatment-calendar', 'agenda:tratamiento:'.$tratamiento->id, 202),
                    $callback(23, 'cb-month-next', 'agenda:calendario:+1', 203),
                ],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 200],
            ]),
            'https://api.telegram.org/*/answerCallbackQuery' => Http::response(['ok' => true, 'result' => true]),
            'https://api.telegram.org/*/editMessageReplyMarkup' => Http::response(['ok' => true, 'result' => true]),
            'https://api.telegram.org/*/editMessageText' => Http::response(['ok' => true, 'result' => ['message_id' => 203]]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/editMessageText')
            && $request['chat_id'] === 345
            && $request['message_id'] === 203
            && str_contains($request['text'], 'Elige una fecha')
            && collect($request['reply_markup']['inline_keyboard'])->flatten(1)
                ->contains(fn (array $button) => $button['callback_data'] === 'agenda:calendario:-1'));
    }

    public function test_un_boton_vencido_no_detiene_el_procesamiento_del_bot(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento->id);
        $chat = ['id' => 456, 'type' => 'private'];

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [
                    ['update_id' => 30, 'message' => ['chat' => $chat, 'text' => '/agendar']],
                    [
                        'update_id' => 31,
                        'callback_query' => [
                            'id' => 'cb-expired',
                            'data' => 'agenda:servicio:'.$servicio->id,
                            'message' => ['message_id' => 301, 'chat' => $chat],
                        ],
                    ],
                ],
            ]),
            'https://api.telegram.org/*/answerCallbackQuery' => Http::response([
                'ok' => false,
                'description' => 'query is too old',
            ], 400),
            'https://api.telegram.org/*/editMessageReplyMarkup' => Http::response(['ok' => true, 'result' => true]),
            'https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 302]]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === 456
            && str_contains($request['text'], '¿Qué tratamiento necesitas?'));
        $this->assertSame(32, cache('dentissa.telegram.offset'));
    }

    public function test_no_avanza_el_cursor_si_falla_el_envio_para_reintentar_el_mensaje(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        Cache::put('dentissa.telegram.offset', 50);

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [[
                    'update_id' => 51,
                    'message' => [
                        'chat' => ['id' => 567, 'type' => 'private'],
                        'text' => '/start',
                    ],
                ]],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => false,
                'description' => 'temporary failure',
            ], 500),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        $this->assertSame(50, Cache::get('dentissa.telegram.offset'));
    }

    public function test_el_bot_guarda_la_cita_solo_despues_de_confirmar_el_resumen(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        config(['dentissa.whatsapp.phone_number_id' => null, 'dentissa.whatsapp.access_token' => null]);
        Mail::fake();

        $servicio = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
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

        $chat = ['id' => 234, 'type' => 'private'];
        $mensaje = fn (int $id, string $texto) => [
            'update_id' => $id,
            'message' => ['chat' => $chat, 'text' => $texto],
        ];
        $callback = fn (int $updateId, string $queryId, string $data) => [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => $queryId,
                'data' => $data,
                'message' => ['message_id' => $updateId, 'chat' => $chat],
            ],
        ];

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [
                    $mensaje(101, '/agendar'),
                    $callback(102, 'cb-servicio', 'agenda:servicio:'.$servicio->id),
                    $callback(103, 'cb-tratamiento', 'agenda:tratamiento:'.$tratamiento->id),
                    $mensaje(104, $fecha->toDateString()),
                    $callback(105, 'cb-horario', 'agenda:horario:09:00'),
                    $mensaje(106, 'Ana'),
                    $mensaje(107, 'Prueba'),
                    $mensaje(108, '+52 755 555 0202'),
                    $mensaje(109, 'ana.telegram@example.test'),
                    $callback(110, 'cb-omitir', 'agenda:motivo:omitir'),
                    $callback(111, 'cb-confirmar-unico', 'agenda:confirmar:si'),
                ],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 1],
            ]),
            'https://api.telegram.org/*/answerCallbackQuery' => Http::response([
                'ok' => true,
                'result' => true,
            ]),
            'https://api.telegram.org/*/editMessageReplyMarkup' => Http::response([
                'ok' => true,
                'result' => true,
            ]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])->assertSuccessful();

        $this->assertDatabaseCount('citas', 1);
        $cita = Cita::firstOrFail();
        $paciente = Paciente::where('email', 'ana.telegram@example.test')->firstOrFail();
        $this->assertSame($paciente->id, $cita->id_paciente);
        $this->assertSame('telegram:cb-confirmar-unico', $cita->clave_idempotencia);
        $this->assertSame(EstadoCita::Confirmada, $cita->estado);
        $this->assertDatabaseMissing('logs_auditoria', [
            'tabla' => 'citas',
            'registro_id' => $cita->id,
            'accion' => 'cita.creada',
        ]);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/answerCallbackQuery')
            && $request['callback_query_id'] === 'cb-confirmar-unico');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && str_contains($request['text'], 'tu cita quedó confirmada'));
    }
}
