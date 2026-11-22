<?php

namespace App\Support;

use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * "Ver cambios" de Control de actividades en palabras de la clínica
 * (22/11/2026). Antes se mostraban los nombres internos de los campos
 * ("Tratamiento ids", "Tipo valor", "Descripcion"), valores crudos
 * ("en_curso", "[1]", "2027-01-31T00:00:00-06:00", "22.00") y también los
 * campos que NO cambiaron.
 *
 * filas() devuelve [['campo' => 'Estado', 'antes' => 'Confirmada', 'despues' => 'En curso'], …]:
 *  - Si hay antes y después: solo lo que cambió.
 *  - Si solo hay después (algo nuevo) o solo antes (algo borrado): sus datos.
 */
class DetalleBitacora
{
    /** Campos internos que no le dicen nada a la clínica. */
    private const OCULTOS = ['id', 'icono', 'created_at', 'updated_at', 'remember_token', 'password', 'activa_legacy'];

    private const CAMPOS = [
        'nombre' => 'Nombre', 'apellido' => 'Apellido', 'titulo' => 'Título', 'descripcion' => 'Descripción',
        'estado' => 'Estado', 'activo' => 'Activo', 'activa' => 'Visible', 'canal' => 'Agendada por',
        'fecha_hora' => 'Fecha y hora', 'fecha_inicio' => 'Inicia', 'fecha_fin' => 'Termina',
        'fecha_consulta' => 'Fecha de la consulta', 'fecha_publicacion' => 'Publicado el',
        'id_tratamiento' => 'Tratamiento', 'id_servicio' => 'Servicio', 'id_paciente' => 'Paciente',
        'id_cita' => 'Cita', 'id_oferta' => 'Oferta', 'tratamiento_ids' => 'Tratamientos',
        'tipo' => 'Tipo', 'tipo_valor' => 'Tipo de descuento', 'valor_descuento' => 'Descuento',
        'precio_especial' => 'Precio especial', 'precio_referencia' => 'Precio',
        'cupo_total' => 'Lugares', 'cupo_usado' => 'Lugares usados', 'duracion_estimada' => 'Duración',
        'sesiones_requeridas' => 'Sesiones', 'requiere_autorizacion' => 'Requiere autorización',
        'categoria' => 'Categoría', 'imagen' => 'Foto', 'foto_perfil' => 'Foto de perfil', 'orden' => 'Orden',
        'campos_modificados' => 'Campos que cambiaron', 'tipo_consulta' => 'Tipo de consulta',
        'motivo' => 'Motivo', 'motivo_cancelacion' => 'Motivo de cancelación', 'notas' => 'Notas',
        'telefono' => 'Teléfono', 'email' => 'Correo', 'rol' => 'Rol', 'calificacion' => 'Calificación',
        'comentario' => 'Comentario', 'pregunta' => 'Pregunta', 'respuesta' => 'Respuesta',
        'variantes' => 'Otras formas de preguntarlo', 'dia_semana' => 'Día', 'abierto' => 'Abre',
        'hora_apertura' => 'Abre a las', 'hora_cierre' => 'Cierra a las', 'capacidad_simultanea' => 'Pacientes a la vez',
        'url' => 'Archivo',
    ];

    /** El mismo dato se llama distinto según dónde se ve en el panel. */
    private const CAMPOS_POR_TABLA = [
        'servicios.activo' => 'Disponible',
        'servicios.orden' => 'Orden en la portada',
        'users.activo' => 'Cuenta activa',
        'ofertas.activa' => 'Publicada',
    ];

    private const VALORES = [
        'estado' => [
            'pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'en_curso' => 'En curso',
            'completada' => 'Completada', 'cancelada' => 'Cancelada', 'no_asistio' => 'No asistió',
            'activa' => 'Publicada', 'inactiva' => 'Oculta', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado',
        ],
        'tipo_consulta' => ['primera_vez' => 'Primera vez', 'seguimiento' => 'Seguimiento', 'urgencia' => 'Urgencia', 'control' => 'Control'],
        'tipo_valor' => ['porcentaje' => 'Porcentaje', 'monto' => 'Cantidad fija', 'monto_fijo' => 'Cantidad fija'],
        'tipo' => ['descuento' => 'Descuento', 'precio_especial' => 'Precio especial', 'gratis' => 'Gratis'],
        'rol' => ['admin' => 'Administración', 'asistente' => 'Asistente'],
        'canal' => ['interna' => 'El consultorio', 'publica' => 'El paciente (en línea)', 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp'],
        'dia_semana' => ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
    ];

    /** @var array<string, array<int, string>> nombres ya buscados (una consulta por tipo y página) */
    private static array $nombres = [];

    /** Olvida los nombres buscados (una vez por página; en pruebas, los id se repiten). */
    public static function reiniciar(): void
    {
        self::$nombres = [];
    }

    /** @return list<array{campo: string, antes: ?string, despues: ?string}> */
    public static function filas(LogAuditoria $registro): array
    {
        $antes = is_array($registro->datos_anteriores) ? $registro->datos_anteriores : [];
        $despues = is_array($registro->datos_nuevos) ? $registro->datos_nuevos : [];
        $hayAmbos = $antes !== [] && $despues !== [];
        $filas = [];

        foreach (array_unique([...array_keys($antes), ...array_keys($despues)]) as $campo) {
            if (in_array($campo, self::OCULTOS, true)) {
                continue;
            }

            $a = array_key_exists($campo, $antes) ? self::valor($campo, $antes[$campo], $antes, $registro->tabla) : null;
            $d = array_key_exists($campo, $despues) ? self::valor($campo, $despues[$campo], $despues, $registro->tabla) : null;

            if ($hayAmbos && $a === $d) {
                continue; // No cambió: solo hace ruido.
            }

            $filas[] = ['campo' => self::campo($campo, $registro->tabla), 'antes' => $a, 'despues' => $d];
        }

        return $filas;
    }

    public static function campo(string $campo, ?string $tabla = null): string
    {
        return self::CAMPOS_POR_TABLA[$tabla.'.'.$campo]
            ?? self::CAMPOS[$campo]
            ?? ucfirst(str_replace('_', ' ', $campo));
    }

    /** @param array<string, mixed> $datos los demás campos del mismo lado (para dar contexto). */
    public static function valor(string $campo, mixed $valor, array $datos = [], ?string $tabla = null): string
    {
        if ($valor === null || $valor === '' || $valor === []) {
            return match ($campo) {
                'imagen', 'foto_perfil' => 'Sin foto',
                'tratamiento_ids' => 'Ninguno',
                default => '—',
            };
        }

        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        return match (true) {
            $campo === 'campos_modificados' => implode(', ', array_map(fn ($c) => self::campo((string) $c, $tabla), (array) $valor)),
            $campo === 'tratamiento_ids' => self::lista(Tratamiento::class, (array) $valor),
            $campo === 'id_tratamiento' => self::nombre(Tratamiento::class, (int) $valor),
            $campo === 'id_servicio' => self::nombre(Servicio::class, (int) $valor),
            $campo === 'id_paciente' => self::nombre(Paciente::class, (int) $valor),
            in_array($campo, ['id_cita', 'id_oferta'], true) => '#'.$valor,
            in_array($campo, ['imagen', 'foto_perfil'], true) => 'Foto subida',
            is_array($valor) => implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $valor)),
            isset(self::VALORES[$campo][$valor]) => self::VALORES[$campo][$valor],
            $campo === 'valor_descuento' => ($datos['tipo_valor'] ?? 'porcentaje') === 'porcentaje' ? self::numero($valor).' %' : '$'.self::numero($valor),
            in_array($campo, ['precio_especial', 'precio_referencia'], true) => '$'.number_format((float) $valor, (float) $valor == (int) $valor ? 0 : 2),
            $campo === 'duracion_estimada' => self::numero($valor).' min',
            $campo === 'calificacion' => $valor.' de 5',
            $campo === 'cupo_total' => (int) $valor === 0 ? 'Sin límite' : (string) $valor,
            in_array($campo, ['hora_apertura', 'hora_cierre'], true) && is_numeric($valor) => sprintf('%d:00', $valor),
            str_starts_with($campo, 'fecha') && is_string($valor) => self::fecha($valor),
            is_numeric($valor) => self::numero($valor),
            default => (string) $valor,
        };
    }

    /** "2027-01-31T00:00:00-06:00" → "31 ene. 2027"; con hora → "21 oct. 2026, 10:30". */
    private static function fecha(string $valor): string
    {
        try {
            $fecha = Carbon::parse($valor)->setTimezone(config('app.timezone'));
        } catch (Throwable) {
            return $valor;
        }

        $soloFecha = strlen($valor) === 10 || $fecha->format('H:i:s') === '00:00:00';

        return $fecha->translatedFormat($soloFecha ? 'j M Y' : 'j M Y, H:i');
    }

    /** "22.00" → "22"; "450.50" → "450.50". */
    private static function numero(mixed $valor): string
    {
        $n = (float) $valor;

        return $n == (int) $n ? (string) (int) $n : number_format($n, 2);
    }

    private static function nombre(string $modelo, int $id): string
    {
        return self::nombres($modelo, [$id])[$id] ?? '#'.$id.' (ya no existe)';
    }

    /** @param array<int|string> $ids */
    private static function lista(string $modelo, array $ids): string
    {
        $nombres = self::nombres($modelo, $ids);

        return implode(', ', array_map(fn ($id) => $nombres[(int) $id] ?? '#'.$id, $ids));
    }

    /**
     * @param array<int|string> $ids
     * @return array<int, string>
     */
    private static function nombres(string $modelo, array $ids): array
    {
        $faltan = array_diff(array_map('intval', $ids), array_keys(self::$nombres[$modelo] ?? []));

        if ($faltan !== []) {
            $encontrados = $modelo::query()->whereKey($faltan)->get()
                ->mapWithKeys(fn ($m) => [$m->getKey() => trim($m->nombre.' '.($m->apellido ?? ''))])->all();
            self::$nombres[$modelo] = (self::$nombres[$modelo] ?? []) + $encontrados;
        }

        return self::$nombres[$modelo] ?? [];
    }
}
