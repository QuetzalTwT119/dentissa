<?php

namespace Database\Seeders;

use App\Enums\EstadoCita;
use App\Enums\EstadoOferta;
use App\Enums\EstadoTestimonio;
use App\Enums\RolUsuario;
use App\Enums\TipoConsulta;
use App\Models\Cita;
use App\Models\Consentimiento;
use App\Models\ExploracionClinica;
use App\Models\HistoriaClinica;
use App\Models\HistorialClinico;
use App\Models\HorarioAtencion;
use App\Models\MensajeContacto;
use App\Models\NotificacionSistema;
use App\Models\Oferta;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\PlanTratamiento;
use App\Models\PlanTratamientoPaso;
use App\Models\PreguntaFrecuente;
use App\Models\Testimonio;
use App\Models\Tratamiento;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Datos FICTICIOS para la presentación (25/11/2026): llena agenda, pacientes,
 * historial, historia clínica, odontograma, planes, pagos, testimonios,
 * mensajes, preguntas frecuentes y avisos, para que ninguna pantalla salga vacía.
 *
 * Reglas de seguridad:
 * - Solo corre con DENTISSA_DEMO_SEED_ENABLED=true (igual que DemoPresentacionSeeder).
 * - Correos @example.invalid y teléfonos 555 (no existen).
 * - Las citas futuras quedan con el recordatorio marcado como enviado: el
 *   programador nunca manda WhatsApp ni correo a estos pacientes.
 * - Los avisos de la campana quedan leídos y atendidos: la campana queda limpia.
 * - No borra nada; si ya existen los pacientes demo, no vuelve a crear datos.
 * - Hoy deja libre de 8:00 a 11:00 para agendar en vivo durante la demo.
 *
 * Uso: php artisan db:seed --class=DemoDatosSeeder --force
 */
class DemoDatosSeeder extends Seeder
{
    public const DOMINIO = 'example.invalid';

    /** Marca para encontrar (y si hace falta borrar) los datos de la demo. */
    public const MARCA = 'demo.';

    private const PACIENTES = [
        ['Ana', 'Ramírez Torres', '1990-03-14', 'femenino', 'Contadora', 'O+'],
        ['Luis', 'Hernández Mora', '1985-07-22', 'masculino', 'Ingeniero civil', 'A+'],
        ['María', 'González Ruiz', '1978-11-05', 'femenino', 'Maestra', 'B+'],
        ['Jorge', 'Martínez Salas', '1995-01-30', 'masculino', 'Diseñador', 'O-'],
        ['Sofía', 'López Castillo', '2001-06-18', 'femenino', 'Estudiante', 'A-'],
        ['Carlos', 'Pérez Navarro', '1969-09-09', 'masculino', 'Comerciante', 'O+'],
        ['Valeria', 'Sánchez Ortiz', '1992-12-01', 'femenino', 'Enfermera', 'AB+'],
        ['Diego', 'Flores Medina', '1988-04-27', 'masculino', 'Chef', 'B-'],
        ['Fernanda', 'Díaz Vargas', '1999-08-13', 'femenino', 'Abogada', 'O+'],
        ['Ricardo', 'Morales Cruz', '1975-02-19', 'masculino', 'Chofer', 'A+'],
        ['Daniela', 'Reyes Jiménez', '1983-10-08', 'femenino', 'Arquitecta', 'O+'],
        ['Andrés', 'Gutiérrez Romero', '2004-05-25', 'masculino', 'Estudiante', 'B+'],
    ];

    /** Horas de inicio (minutos desde medianoche); separadas 90 min, nunca se enciman. */
    private const HORAS = [9 * 60, 10 * 60 + 30, 12 * 60, 13 * 60 + 30, 15 * 60 + 30, 17 * 60];

    private CarbonImmutable $hoy;

    private int $doctorId;

    public function run(): void
    {
        if (config('services.demo_presentacion.habilitada') !== true) {
            throw new RuntimeException(
                'El seeder de datos demo está deshabilitado. Activa DENTISSA_DEMO_SEED_ENABLED solo para ejecutarlo manualmente.'
            );
        }

        if (Paciente::where('email', 'like', self::MARCA.'%@'.self::DOMINIO)->exists()) {
            $this->command?->warn('Los datos demo ya existen; no se crea nada nuevo.');

            return;
        }

        $doctor = User::whereIn('rol', [RolUsuario::SuperAdmin, RolUsuario::Doctor])->where('activo', true)->orderBy('id')->first();
        if (! $doctor) {
            throw new RuntimeException('Primero crea la cuenta del administrador (DemoPresentacionSeeder).');
        }
        $this->doctorId = $doctor->id;
        $this->hoy = CarbonImmutable::today();

        if (Tratamiento::where('activo', true)->doesntExist()) {
            $this->call(ServiciosTratamientosSeeder::class);
        }

        DB::transaction(function (): void {
            $pacientes = $this->crearPacientes();
            $citas = $this->crearCitas($pacientes);
            $this->crearClinico($pacientes, $citas);
            $this->crearPublico($pacientes, $citas);
            $this->crearAvisos($citas);
        });

        $this->command?->info('Datos demo creados: '.count(self::PACIENTES).' pacientes y '.Cita::whereHas('paciente', fn ($q) => $q->where('email', 'like', self::MARCA.'%'))->count().' citas.');
    }

    /** @return list<Paciente> */
    private function crearPacientes(): array
    {
        $pacientes = [];

        foreach (self::PACIENTES as $i => [$nombre, $apellido, $nacimiento, $sexo, $ocupacion, $sangre]) {
            $correo = self::MARCA.$this->slug($nombre).'.'.$this->slug(strtok($apellido, ' ')).'@'.self::DOMINIO;

            $paciente = Paciente::create([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $correo,
                'telefono' => '55501000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'fecha_nacimiento' => $nacimiento,
                'ciudad' => 'Ciudad de México',
                'ocupacion' => $ocupacion,
                'tipo_sangre' => $sangre,
            ]);
            $paciente->forceFill(['aviso_privacidad_aceptado_en' => now()->subDays(30 - $i)])->save();

            $pacientes[] = $paciente;
        }

        return $pacientes;
    }

    /** @param list<Paciente> $pacientes @return array{pasadas: list<Cita>, futuras: list<Cita>} */
    private function crearCitas(array $pacientes): array
    {
        $tratamientos = Tratamiento::where('activo', true)->with('servicios')->orderBy('id')->get();
        $horarios = HorarioAtencion::all()->keyBy('dia_semana');
        $pasadas = [];
        $futuras = [];
        $n = 0;

        // Tres semanas atrás → dos semanas adelante.
        for ($d = -21; $d <= 14; $d++) {
            $dia = $this->hoy->addDays($d);
            $horario = $horarios->get($dia->dayOfWeek);
            if ($dia->isSunday() || ($horario && ! $horario->abierto)) {
                continue;
            }

            $horas = self::HORAS;
            if ($dia->isSaturday()) {
                $horas = [9 * 60 + 30, 11 * 60];
            }
            if ($d === 0) {
                // Hoy: libre la mañana para agendar en vivo.
                $horas = [11 * 60 + 30, 13 * 60, 16 * 60, 17 * 60];
            }

            // 2 a 4 citas por día según el día; hoy, todas.
            $cuantas = $d === 0 ? count($horas) : min(count($horas), 2 + (abs($d) % 3));

            foreach (array_slice($horas, 0, $cuantas) as $k => $minutos) {
                $paciente = $pacientes[$n % count($pacientes)];
                $tratamiento = $tratamientos[($n + $k) % $tratamientos->count()];
                $inicio = $dia->addMinutes($minutos);

                if ($horario && ($inicio->hour < $horario->hora_apertura || $inicio->addMinutes($tratamiento->duracion_estimada)->hour > $horario->hora_cierre)) {
                    continue;
                }

                $estado = $this->estadoPara($d, $n);
                $creada = $d < 0 ? $inicio->subDays(5) : now()->subDays(3);

                $cita = new Cita([
                    'id_paciente' => $paciente->id,
                    'id_servicio' => $tratamiento->servicios->first()?->id,
                    'id_tratamiento' => $tratamiento->id,
                    'fecha_hora' => $inicio,
                    'duracion_estimada' => $tratamiento->duracion_estimada,
                    'estado' => $estado,
                    'motivo' => $this->motivo($tratamiento->nombre),
                    // Nunca recordar a pacientes ficticios.
                    'recordatorio_enviado_en' => $d < 0 ? $inicio->subDay() : now(),
                    'asistencia_confirmada_en' => $estado === EstadoCita::Confirmada ? now()->subHours(2) : null,
                    'fecha_confirmacion' => in_array($estado, [EstadoCita::Confirmada, EstadoCita::Completada], true) ? $creada : null,
                    'confirmada_por' => in_array($estado, [EstadoCita::Confirmada, EstadoCita::Completada], true) ? $this->doctorId : null,
                    'fecha_cancelacion' => $estado === EstadoCita::Cancelada ? $inicio->subDays(2) : null,
                    'cancelada_por' => $estado === EstadoCita::Cancelada ? $this->doctorId : null,
                    'motivo_cancelacion' => $estado === EstadoCita::Cancelada ? 'El paciente avisó que tenía un compromiso de trabajo.' : null,
                ]);
                $cita->created_at = $creada;
                $cita->updated_at = $creada;
                $cita->save();
                $cita->setRelation('tratamiento', $tratamiento);

                $d < 0 ? $pasadas[] = $cita : $futuras[] = $cita;
                $n++;
            }
        }

        return compact('pasadas', 'futuras');
    }

    private function estadoPara(int $dia, int $n): EstadoCita
    {
        if ($dia < 0) {
            return match (true) {
                $n % 9 === 4 => EstadoCita::NoAsistio,
                $n % 11 === 7 => EstadoCita::Cancelada,
                default => EstadoCita::Completada,
            };
        }

        if ($dia > 0 && $n % 13 === 5) {
            return EstadoCita::Cancelada;
        }

        return $n % 2 === 0 ? EstadoCita::Confirmada : EstadoCita::Agendada;
    }

    /** @param list<Paciente> $pacientes @param array{pasadas: list<Cita>, futuras: list<Cita>} $citas */
    private function crearClinico(array $pacientes, array $citas): void
    {
        $atendidas = collect($citas['pasadas'])->filter(fn (Cita $c) => $c->estado === EstadoCita::Completada);

        // Historial y pago de cada cita atendida.
        $primera = [];
        foreach ($atendidas as $i => $cita) {
            $nombre = $cita->tratamiento?->nombre ?? 'Valoración';
            $esPrimera = ! isset($primera[$cita->id_paciente]);
            $primera[$cita->id_paciente] = true;

            HistorialClinico::create([
                'id_paciente' => $cita->id_paciente,
                'id_cita' => $cita->id,
                'id_doctor' => $this->doctorId,
                'fecha_consulta' => $cita->fecha_hora->toDateString(),
                'motivo_consulta' => $cita->motivo,
                'diagnostico' => $this->diagnostico($nombre),
                'tratamiento_realizado' => $nombre.' sin complicaciones.',
                'dientes' => $this->dientes($nombre, $i),
                'medicamentos_recetados' => $nombre === 'Extracción Dental' ? 'Ibuprofeno 400 mg cada 8 h por 3 días.' : null,
                'observaciones' => 'Paciente tranquilo; se dieron indicaciones de cuidado en casa.',
                'tipo_consulta' => $esPrimera ? TipoConsulta::PrimeraVez : TipoConsulta::Seguimiento,
            ]);

            $precio = (float) ($cita->tratamiento?->precio_referencia ?? 300);
            Pago::create([
                'id_paciente' => $cita->id_paciente,
                'id_cita' => $cita->id,
                'monto' => $i % 5 === 3 ? round($precio / 2, 2) : $precio,
                'metodo' => array_keys(Pago::METODOS)[$i % 3],
                'fecha' => $cita->fecha_hora->toDateString(),
                'concepto' => $nombre.($i % 5 === 3 ? ' (anticipo)' : ''),
                'registrado_por' => $this->doctorId,
            ]);
        }

        // Historia clínica (cuestionario) de los primeros 8 pacientes.
        foreach (array_slice($pacientes, 0, 8) as $i => $p) {
            [, , $nacimiento, $sexo, $ocupacion, $sangre] = self::PACIENTES[$i];
            HistoriaClinica::create([
                'id_paciente' => $p->id,
                'fecha_nacimiento' => $nacimiento,
                'sexo' => $sexo,
                'ocupacion' => $ocupacion,
                'estado_civil' => ['casado', 'soltero', 'union_libre', 'casado'][$i % 4],
                'tipo_sangre' => $sangre,
                'antecedentes_familiares' => [['diabetes'], ['hipertension'], [], ['diabetes', 'cardiopatia']][$i % 4],
                'antecedentes_personales' => [[], ['hipertension'], ['asma'], [], ['gastritis']][$i % 5],
                'alergias' => $i % 4 === 1 ? ['penicilina'] : [],
                'medicamentos_actuales' => $i % 5 === 1 ? 'Losartán 50 mg diario' : null,
                'embarazo' => false,
                'lactancia' => false,
                'antecedentes_dentales' => [['sensibilidad'], ['sangrado_encias'], ['ortodoncia_previa'], []][$i % 4],
                'ultima_visita_dentista' => 'Hace '.(1 + $i % 3).' años',
                'habitos' => [['bruxismo'], [], ['fuma'], ['muerde_objetos']][$i % 4],
                'contacto_emergencia_nombre' => 'Familiar de '.$p->nombre,
                'contacto_emergencia_parentesco' => ['Esposo(a)', 'Mamá', 'Hermano(a)', 'Papá'][$i % 4],
                'contacto_emergencia_telefono' => '55502000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'actualizado_por' => $this->doctorId,
            ]);
        }

        // Exploración con odontograma de los primeros 5.
        $odontogramas = [
            ['16' => 'cariado', '26' => 'obturado', '36' => 'obturado', '46' => 'cariado', '18' => 'ausente', '11' => 'sano', '21' => 'sano'],
            ['17' => 'endodoncia', '27' => 'obturado', '38' => 'extraccion_indicada', '48' => 'extraido', '14' => 'cariado'],
            ['36' => 'necesita_endodoncia', '35' => 'cariado', '25' => 'obturado', '16' => 'obturado'],
            ['18' => 'extraido', '28' => 'extraido', '38' => 'extraido', '48' => 'extraido', '46' => 'necesita_sellante', '36' => 'necesita_sellante'],
            ['11' => 'obturado', '21' => 'cariado', '22' => 'cariado', '47' => 'endodoncia'],
        ];
        foreach (array_slice($pacientes, 0, 5) as $i => $p) {
            ExploracionClinica::create([
                'id_paciente' => $p->id,
                'id_doctor' => $this->doctorId,
                'fecha' => $this->hoy->subDays(14 - $i)->toDateString(),
                'estomatologico' => collect(ExploracionClinica::ESTRUCTURAS)->keys()
                    ->mapWithKeys(fn ($e) => [$e => $e === 'lengua' && $i % 2 ? ['normal' => false, 'hallazgo' => 'Lengua saburral leve'] : ['normal' => true, 'hallazgo' => null]])->all(),
                'atm' => $i === 2 ? 'Chasquido leve al abrir del lado derecho.' : 'Sin alteraciones.',
                'periodontal' => $i % 2 ? ['sangrado' => '16, 26', 'calculo' => '31, 32, 41, 42'] : ['calculo' => '41, 42'],
                'higiene_oral' => ['buena', 'regular', 'mala', 'regular', 'buena'][$i],
                'pulpares' => $i === 2 ? [['diente' => 36, 'signos' => 'Dolor espontáneo nocturno', 'diagnostico' => 'Pulpitis irreversible']] : [],
                'radiografica' => $i === 1 ? [['diente' => 38, 'hallazgo' => 'Tercer molar semi-incluido']] : [],
                'odontograma' => $odontogramas[$i],
                'diagnosticos' => ['Caries en 16 y 46; gingivitis leve.', 'Tercer molar 38 por extraer.', 'Pulpitis en 36; requiere endodoncia.', 'Molares sanos; sellantes preventivos.', 'Caries en incisivos 21 y 22.'][$i],
            ]);
        }

        // Planes de tratamiento.
        $porNombre = Tratamiento::pluck('id', 'nombre');
        $planes = [
            [0, 'Rehabilitación de molares', 'activo', [['Retiro de Caries', '16', 700, true], ['Retiro de Caries', '46', 700, false], ['Limpieza Dental', null, 600, false]]],
            [1, 'Extracción de tercer molar', 'activo', [['Valoración', null, 300, true], ['Extracción Dental', '38', 900, false]]],
            [2, 'Tratamiento del 36', 'activo', [['Valoración', '36', 300, true], ['Retiro de Caries', '35', 700, true], ['Limpieza Dental', null, 600, false]]],
            [4, 'Estética anterior', 'terminado', [['Retiro de Caries', '21, 22', 1400, true], ['Blanqueamiento Dental', null, 3500, true]]],
        ];
        foreach ($planes as [$idx, $titulo, $estado, $pasos]) {
            $plan = PlanTratamiento::create([
                'id_paciente' => $pacientes[$idx]->id,
                'id_doctor' => $this->doctorId,
                'titulo' => $titulo,
                'estado' => $estado,
                'notas' => 'Plan acordado con el paciente; precios de referencia.',
            ]);
            foreach ($pasos as $orden => [$trat, $dientes, $costo, $hecho]) {
                PlanTratamientoPaso::create([
                    'id_plan' => $plan->id,
                    'id_tratamiento' => $porNombre[$trat] ?? null,
                    'descripcion' => $trat,
                    'dientes' => $dientes,
                    'costo' => $costo,
                    'hecho_en' => $hecho ? $this->hoy->subDays(10 - $orden * 3) : null,
                    'orden' => $orden + 1,
                ]);
            }
        }

        Consentimiento::create([
            'id_paciente' => $pacientes[1]->id,
            'id_doctor' => $this->doctorId,
            'procedimiento' => 'Extracción de tercer molar',
            'dientes' => '38',
            'beneficios' => 'Elimina el dolor y evita infecciones y daño al diente vecino.',
            'riesgos' => 'Inflamación, sangrado leve, dolor por unos días y, rara vez, adormecimiento temporal del labio.',
            'alternativas' => 'Vigilar el diente con radiografías periódicas.',
        ]);
    }

    /** @param list<Paciente> $pacientes @param array{pasadas: list<Cita>, futuras: list<Cita>} $citas */
    private function crearPublico(array $pacientes, array $citas): void
    {
        $atendidas = collect($citas['pasadas'])->filter(fn (Cita $c) => $c->estado === EstadoCita::Completada)->unique('id_paciente')->values();
        $comentarios = [
            [5, 'Me atendieron muy rápido y la doctora me explicó todo con paciencia. ¡Muy recomendable!', EstadoTestimonio::Aprobado],
            [5, 'Agendé desde el celular en un minuto y me llegó el recordatorio un día antes.', EstadoTestimonio::Aprobado],
            [4, 'Excelente trato y el consultorio muy limpio. La limpieza fue sin dolor.', EstadoTestimonio::Aprobado],
            [5, 'Llevé a mi hijo con miedo al dentista y salió feliz. Gracias, doctora.', EstadoTestimonio::Aprobado],
            [5, 'El blanqueamiento quedó increíble, justo como lo platicamos.', EstadoTestimonio::Pendiente],
        ];
        foreach ($comentarios as $i => [$estrellas, $texto, $estado]) {
            $cita = $atendidas[$i % max(1, $atendidas->count())] ?? null;
            Testimonio::create([
                'id_paciente' => $cita?->id_paciente ?? $pacientes[$i]->id,
                'id_cita' => $cita?->id,
                'calificacion' => $estrellas,
                'comentario' => $texto,
                'estado' => $estado,
                'fecha_publicacion' => $estado === EstadoTestimonio::Aprobado ? now()->subDays(10 - $i) : null,
            ]);
        }

        $mensajes = [
            ['Roberto Aguilar', '¿Atienden los sábados? Quisiera una valoración para mi esposa.', true],
            ['Paola Méndez', 'Hola, ¿cuánto cuesta el blanqueamiento y cuántas sesiones son?', false],
            ['Héctor Ríos', '¿Aceptan pago con tarjeta? Gracias.', false],
        ];
        foreach ($mensajes as $i => [$nombre, $texto, $leido]) {
            $m = MensajeContacto::create([
                'nombre' => $nombre,
                'email' => self::MARCA.'contacto'.($i + 1).'@'.self::DOMINIO,
                'telefono' => '55503000'.($i + 1).'0',
                'mensaje' => $texto,
                'leido' => $leido,
            ]);
            $m->created_at = now()->subDays(3 - $i)->subHours(2);
            $m->save();
        }

        if (PreguntaFrecuente::count() === 0) {
            $faq = [
                ['¿Necesito cuenta para agendar?', 'No. Eliges tratamiento, fecha y hora, dejas tus datos y listo.', 'cuenta, registrarme, usuario'],
                ['¿Cuál es el horario de atención?', 'De lunes a viernes de 8:00 a 18:00 y sábados de 9:00 a 14:00.', 'horario, hora, abren, cierran, sábado'],
                ['¿Cómo cancelo o cambio mi cita?', 'Desde el recordatorio de WhatsApp puedes confirmar, cancelar o pedir otro horario con un toque.', 'cancelar, cambiar, reagendar, mover'],
                ['¿Qué formas de pago aceptan?', 'Efectivo, tarjeta y transferencia.', 'pago, tarjeta, efectivo, transferencia'],
                ['¿La valoración tiene costo?', 'Sí, la valoración cuesta $300 e incluye la revisión completa y tu plan de tratamiento.', 'valoración, revisión, costo, precio'],
            ];
            foreach ($faq as $i => [$pregunta, $respuesta, $variantes]) {
                PreguntaFrecuente::create(['pregunta' => $pregunta, 'respuesta' => $respuesta, 'variantes' => $variantes, 'activa' => true, 'orden' => $i + 1]);
            }
        }

        $limpieza = Tratamiento::where('nombre', 'Limpieza Dental')->first();
        if ($limpieza && Oferta::count() === 0) {
            Oferta::create([
                ...Oferta::columnasDeBeneficio('porcentaje', 20),
                'titulo' => 'Limpieza de fin de año',
                'descripcion' => '20 % de descuento en tu limpieza dental durante diciembre.',
                'id_tratamiento' => $limpieza->id,
                'fecha_inicio' => $this->hoy->toDateString(),
                'fecha_fin' => $this->hoy->addMonth()->endOfMonth()->toDateString(),
                'cupo_total' => 20,
                'cupos_utilizados' => 3,
                'estado' => EstadoOferta::Activa,
            ]);
        }
    }

    /** Historial de la campana: todo leído y atendido, sin pendientes. @param array{pasadas: list<Cita>, futuras: list<Cita>} $citas */
    private function crearAvisos(array $citas): void
    {
        $personal = User::where('activo', true)->pluck('id');
        $recientes = collect($citas['pasadas'])->slice(-6)->merge(collect($citas['futuras'])->take(4));
        $eventos = [
            EstadoCita::Completada->value => ['completada', 'Cita completada', 'Se completó una cita programada para'],
            EstadoCita::NoAsistio->value => ['no_asistio', 'Inasistencia registrada', 'Se registró una inasistencia para la cita de'],
            EstadoCita::Cancelada->value => ['cancelada', 'Cita cancelada', 'Se canceló una cita que estaba programada para'],
            EstadoCita::Confirmada->value => ['asistencia_confirmada', 'Paciente confirmó asistencia', 'El paciente confirmó que asistirá a su cita del'],
            EstadoCita::Agendada->value => ['agendada', 'Nueva cita registrada', 'Se registró una cita para'],
        ];

        foreach ($recientes as $cita) {
            [$evento, $titulo, $prefijo] = $eventos[$cita->estado->value];
            foreach ($personal as $userId) {
                $aviso = NotificacionSistema::create([
                    'user_id' => $userId,
                    'id_cita' => $cita->id,
                    'evento' => $evento,
                    'titulo' => $titulo,
                    'mensaje' => $prefijo.' '.$cita->fecha_hora->translatedFormat('d M Y, H:i').'.',
                    'url' => route('citas.index', [], false),
                    'leida_en' => now(),
                    'vista_en' => now(),
                    'atendida_en' => now(),
                ]);
                $momento = min($cita->fecha_hora, now()->subMinutes(30));
                $aviso->created_at = $momento;
                $aviso->updated_at = $momento;
                $aviso->save();
            }
        }
    }

    private function motivo(string $tratamiento): string
    {
        return match ($tratamiento) {
            'Valoración' => 'Revisión general; hace tiempo que no visita al dentista.',
            'Limpieza Dental' => 'Limpieza de rutina.',
            'Retiro de Caries' => 'Molestia al comer cosas frías.',
            'Extracción Dental' => 'Dolor en muela del juicio.',
            'Blanqueamiento Dental' => 'Quiere dientes más blancos para un evento.',
            default => $tratamiento,
        };
    }

    private function diagnostico(string $tratamiento): string
    {
        return match ($tratamiento) {
            'Valoración' => 'Caries incipiente y acumulación de sarro.',
            'Limpieza Dental' => 'Gingivitis leve por placa bacteriana.',
            'Retiro de Caries' => 'Caries de segundo grado.',
            'Extracción Dental' => 'Tercer molar con caries extensa.',
            'Blanqueamiento Dental' => 'Pigmentación extrínseca por café.',
            default => 'Sin hallazgos relevantes.',
        };
    }

    private function dientes(string $tratamiento, int $i): ?string
    {
        return match ($tratamiento) {
            'Retiro de Caries' => ['16', '46', '26', '36'][$i % 4],
            'Extracción Dental' => ['38', '48'][$i % 2],
            default => null,
        };
    }

    private function slug(string $texto): string
    {
        return strtolower(str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
            $texto,
        ));
    }
}
