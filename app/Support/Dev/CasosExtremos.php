<?php

namespace App\Support\Dev;

use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Datos de prueba "peor caso" para revisar la interfaz (Fase 4 del pulido).
 *
 * Todo se arma EN MEMORIA: ningún modelo se guarda en la base de datos.
 * Los valores son realistas o están justo en el límite que acepta el sistema
 * (nombre y apellido 100, correo 100, tratamiento 150, servicio 100).
 *
 * Conjuntos: demo | peor | vacio | uno | muchos
 */
class CasosExtremos
{
    public const CONJUNTOS = ['demo', 'peor', 'vacio', 'uno', 'muchos'];

    public const NOMBRE_LARGO = 'María Guadalupe de los Ángeles';

    public const APELLIDO_LARGO = 'Wiśniewska-Kowalczyk Fernández de Córdoba y Montenegro';

    public const CORREO_LARGO = 'bartholomew.fitzgerald@clinica-dental-integral-del-pacifico.example.com';

    public const TRATAMIENTO_LARGO = 'Rehabilitación oral completa con coronas de zirconia, incrustaciones estéticas y ajuste oclusal guiado por férula de descarga nocturna';

    public const SERVICIO_LARGO = 'Odontología Estética, Restauradora y Rehabilitación Integral del Adulto Mayor';

    public static function conjunto(?string $valor): string
    {
        return in_array($valor, self::CONJUNTOS, true) ? $valor : 'peor';
    }

    /** Usuario del personal (para la barra del panel y el saludo). */
    public static function usuario(string $conjunto): User
    {
        $usuario = new User;
        $usuario->forceFill([
            'id' => 999_999,
            'nombre' => $conjunto === 'peor' ? self::NOMBRE_LARGO : 'Melissa',
            'apellido' => $conjunto === 'peor' ? self::APELLIDO_LARGO : 'López N.',
            'email' => $conjunto === 'peor' ? self::CORREO_LARGO : 'admin@dentissa.test',
            'rol' => RolUsuario::Admin,
            'foto_perfil' => null,
        ]);

        return $usuario;
    }

    /** @return array<int, array{nombre:string, apellido:string, telefono:?string, email:?string}> */
    private static function personas(): array
    {
        return [
            ['nombre' => self::NOMBRE_LARGO, 'apellido' => self::APELLIDO_LARGO, 'telefono' => '7551020089', 'email' => self::CORREO_LARGO],
            ['nombre' => 'Jo', 'apellido' => 'Li', 'telefono' => null, 'email' => self::CORREO_LARGO],
            ['nombre' => 'Ñandú', 'apellido' => 'Ötzi Ybáñez', 'telefono' => null, 'email' => null],
            ['nombre' => '😊 Ana', 'apellido' => 'Pérez', 'telefono' => '5512345678', 'email' => 'ana@correo.com'],
            ['nombre' => 'Christopher Alexander', 'apellido' => 'Montgomery-Valenzuela III', 'telefono' => '7551020090', 'email' => 'c.montgomery@example.com'],
        ];
    }

    private static function paciente(int $id, array $datos, int $consultas = 0): Paciente
    {
        $paciente = new Paciente;
        $paciente->forceFill($datos + ['id' => $id, 'tutor_id' => null, 'historial_clinico_count' => $consultas]);
        $paciente->exists = true;
        $paciente->setRelation('tutor', null);

        return $paciente;
    }

    private static function tratamiento(int $id, string $nombre, int $minutos): Tratamiento
    {
        $tratamiento = new Tratamiento;
        $tratamiento->forceFill(['id' => $id, 'nombre' => $nombre, 'duracion_estimada' => $minutos, 'activo' => true]);
        $tratamiento->exists = true;

        return $tratamiento;
    }

    private static function servicio(int $id, string $nombre, Collection $tratamientos): Servicio
    {
        $servicio = new Servicio;
        $servicio->forceFill(['id' => $id, 'nombre' => $nombre, 'activo' => true]);
        $servicio->exists = true;
        $servicio->setRelation('tratamientos', $tratamientos);

        return $servicio;
    }

    /** Servicios y tratamientos para el formulario público de agendar. */
    public static function servicios(string $conjunto): Collection
    {
        if ($conjunto === 'vacio') {
            return collect();
        }

        $demo = collect([
            self::servicio(1, 'Odontología General', collect([
                self::tratamiento(1, 'Limpieza Dental Profunda', 60),
                self::tratamiento(2, 'Extracción Simple', 45),
            ])),
            self::servicio(2, 'Ortodoncia', collect([self::tratamiento(3, 'Valoración de Ortodoncia', 30)])),
        ]);

        if ($conjunto === 'demo') {
            return $demo;
        }

        if ($conjunto === 'uno') {
            return collect([self::servicio(1, 'Odontología General', collect([self::tratamiento(1, 'Limpieza', 1)]))]);
        }

        // Peor caso y "muchos": nombres en el límite, servicio sin tratamientos,
        // duraciones de 1 y de 480 minutos, y muchos tratamientos en un servicio.
        $muchos = collect(range(1, $conjunto === 'muchos' ? 40 : 12))
            ->map(fn (int $i) => self::tratamiento(100 + $i, "Tratamiento de prueba número {$i} con nombre descriptivo", 30 + $i));

        return collect([
            self::servicio(10, self::SERVICIO_LARGO, collect([
                self::tratamiento(10, self::TRATAMIENTO_LARGO, 480),
                self::tratamiento(11, 'Jo', 1),
            ])),
            self::servicio(11, 'Servicio sin tratamientos disponibles', collect()),
            self::servicio(12, 'Odontopediatría', $muchos),
            ...$demo,
        ]);
    }

    /** Página de citas para la agenda. */
    public static function citas(string $conjunto): LengthAwarePaginator
    {
        $porPagina = 20;
        [$total, $cantidad] = match ($conjunto) {
            'vacio' => [0, 0],
            'uno' => [1, 1],
            'muchos' => [1284, $porPagina],
            'demo' => [3, 3],
            default => [27, $porPagina],
        };

        $estados = ['agendada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];
        $personas = $conjunto === 'demo'
            ? [['nombre' => 'Ana', 'apellido' => 'Pérez', 'telefono' => '5512345678', 'email' => 'ana@correo.com']]
            : self::personas();
        $servicios = $conjunto === 'demo'
            ? [['Odontología General', 'Limpieza Dental Profunda']]
            : [[self::SERVICIO_LARGO, self::TRATAMIENTO_LARGO], ['Ortodoncia', 'Jo'], ['Odontología General', 'Limpieza Dental Profunda']];

        $base = Carbon::now()->startOfDay()->addDays(2)->setTime(9, 0);
        $citas = collect(range(0, max($cantidad - 1, -1)))->filter(fn ($i) => $i >= 0 && $cantidad > 0)->map(function (int $i) use ($estados, $personas, $servicios, $base) {
            [$servicioNombre, $tratamientoNombre] = $servicios[$i % count($servicios)];

            $cita = new Cita;
            $cita->forceFill([
                'id' => 9000 + $i,
                'fecha_hora' => $base->copy()->addMinutes(30 * $i),
                'estado' => $estados[$i % count($estados)],
                'asistencia_confirmada_en' => null,
            ]);
            $cita->exists = true;
            $cita->setRelation('paciente', self::paciente(8000 + $i, $personas[$i % count($personas)]));
            $cita->setRelation('servicio', self::servicio(7000 + $i, $servicioNombre, collect()));
            $cita->setRelation('tratamiento', self::tratamiento(6000 + $i, $tratamientoNombre, 60));

            return $cita;
        })->values();

        return new LengthAwarePaginator($citas, $total, $porPagina, 1, ['path' => request()->url(), 'query' => request()->query()]);
    }

    /** Página de pacientes para el directorio. */
    public static function pacientes(string $conjunto): LengthAwarePaginator
    {
        $porPagina = 20;
        [$total, $cantidad] = match ($conjunto) {
            'vacio' => [0, 0],
            'uno' => [1, 1],
            'muchos' => [1284, $porPagina],
            'demo' => [2, 2],
            default => [5, 5],
        };

        $personas = $conjunto === 'demo'
            ? [['nombre' => 'Ana', 'apellido' => 'Pérez', 'telefono' => '5512345678', 'email' => 'ana@correo.com']]
            : self::personas();
        $consultas = [0, 1, 1284, 12, 3];

        $pacientes = collect(range(0, max($cantidad - 1, 0)))
            ->take($cantidad)
            ->map(fn (int $i) => self::paciente(5000 + $i, $personas[$i % count($personas)], $consultas[$i % count($consultas)]));

        return new LengthAwarePaginator($pacientes, $total, $porPagina, 1, ['path' => request()->url(), 'query' => request()->query()]);
    }

    /** Resumen que muestra la pantalla de confirmación al agendar. */
    public static function resumenConfirmacion(string $conjunto): array
    {
        return [
            'tratamiento' => $conjunto === 'peor' ? self::TRATAMIENTO_LARGO : 'Limpieza Dental Profunda',
            'fecha' => 'miércoles 26 de noviembre de 2026',
            'hora' => '10:00',
        ];
    }
}
