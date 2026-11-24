<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Cuestionario de historia clínica (anamnesis), una por paciente (22/11/2026).
 *
 * Las listas guardan solo las claves marcadas. Para agregar una opción basta
 * con sumarla a la constante correspondiente (no cambia la base de datos).
 * RIESGO_* marca lo que sale en el aviso rojo antes de atender.
 */
#[Fillable([
    'id_paciente', 'fecha_nacimiento', 'sexo', 'ocupacion', 'estado_civil', 'tipo_sangre',
    'antecedentes_familiares', 'antecedentes_familiares_notas',
    'antecedentes_personales', 'antecedentes_personales_notas',
    'alergias', 'alergias_otras', 'medicamentos_actuales', 'tratamiento_medico_actual',
    'embarazo', 'lactancia',
    'antecedentes_dentales', 'ultima_visita_dentista', 'antecedentes_dentales_notas',
    'habitos', 'habitos_notas', 'observaciones', 'actualizado_por',
    'contacto_emergencia_nombre', 'contacto_emergencia_parentesco', 'contacto_emergencia_telefono',
    'medico_cabecera_nombre', 'medico_cabecera_telefono',
])]
class HistoriaClinica extends Model
{
    protected $table = 'historias_clinicas';

    public const SEXOS = ['femenino' => 'Femenino', 'masculino' => 'Masculino', 'otro' => 'Otro'];

    public const TIPOS_SANGRE = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    public const ESTADOS_CIVILES = [
        'soltero' => 'Soltero(a)', 'casado' => 'Casado(a)', 'union_libre' => 'Unión libre',
        'divorciado' => 'Divorciado(a)', 'viudo' => 'Viudo(a)',
    ];

    public const ANTECEDENTES_FAMILIARES = [
        'diabetes' => 'Diabetes',
        'hipertension' => 'Hipertensión',
        'cardiopatia' => 'Enfermedades del corazón',
        'cancer' => 'Cáncer',
        'enfermedad_renal' => 'Enfermedad de los riñones',
    ];

    public const ANTECEDENTES_PERSONALES = [
        'diabetes' => 'Diabetes',
        'hipertension' => 'Hipertensión',
        'cardiopatia' => 'Enfermedad del corazón',
        'asma' => 'Asma',
        'epilepsia' => 'Epilepsia o convulsiones',
        'hepatitis' => 'Hepatitis',
        'coagulacion' => 'Problemas de coagulación o sangrado',
        'tiroides' => 'Tiroides',
        'vih' => 'VIH',
        'enfermedad_renal' => 'Enfermedad de los riñones',
        'cancer' => 'Cáncer o quimioterapia',
        // 23/11/2026, del formato de historia clínica odontológica.
        'respiratoria' => 'Otra enfermedad respiratoria',
        'gastritis' => 'Gastritis o úlcera',
        'cirugias' => 'Cirugías previas',
    ];

    public const ALERGIAS = [
        'penicilina' => 'Penicilina',
        'anestesia_local' => 'Anestesia local',
        'aines' => 'Antiinflamatorios (aspirina, ibuprofeno)',
        'latex' => 'Látex',
    ];

    public const ANTECEDENTES_DENTALES = [
        'sangrado_encias' => 'Le sangran las encías',
        'sensibilidad' => 'Sensibilidad al frío o al calor',
        'ortodoncia_previa' => 'Usó ortodoncia (brackets)',
        'reaccion_anestesia' => 'Tuvo una mala reacción a la anestesia',
        'dolor_mandibula' => 'Dolor o chasquido en la mandíbula',
    ];

    public const HABITOS = [
        'fuma' => 'Fuma',
        'alcohol' => 'Consume alcohol',
        'bruxismo' => 'Aprieta o rechina los dientes',
        'muerde_objetos' => 'Muerde uñas u objetos',
    ];

    /** Condiciones personales que la doctora debe ver antes de atender. */
    public const RIESGO_PERSONALES = ['coagulacion', 'cardiopatia', 'hipertension', 'diabetes', 'epilepsia', 'vih', 'hepatitis'];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'antecedentes_familiares' => 'array',
            'antecedentes_personales' => 'array',
            'alergias' => 'array',
            'antecedentes_dentales' => 'array',
            'habitos' => 'array',
            'embarazo' => 'boolean',
            'lactancia' => 'boolean',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function edad(): ?int
    {
        return $this->fecha_nacimiento ? (int) $this->fecha_nacimiento->diffInYears(Carbon::today()) : null;
    }

    /**
     * Lo que sale en el aviso rojo: alergias, condiciones de riesgo, embarazo
     * y reacción previa a la anestesia.
     *
     * @return list<string>
     */
    public function alertas(): array
    {
        $alertas = [];

        $alergias = self::nombres($this->alergias, self::ALERGIAS);
        if (filled($this->alergias_otras)) {
            $alergias[] = trim($this->alergias_otras);
        }
        if ($alergias !== []) {
            $alertas[] = 'Alergia: '.implode(', ', $alergias);
        }

        $riesgos = self::nombres(array_intersect((array) $this->antecedentes_personales, self::RIESGO_PERSONALES), self::ANTECEDENTES_PERSONALES);
        foreach ($riesgos as $riesgo) {
            $alertas[] = $riesgo;
        }

        if ($this->sexo === 'femenino' && $this->embarazo) {
            $alertas[] = 'Embarazo';
        }
        if (in_array('reaccion_anestesia', (array) $this->antecedentes_dentales, true)) {
            $alertas[] = 'Mala reacción previa a la anestesia';
        }

        return $alertas;
    }

    /**
     * @param  array<int, string>|null  $claves
     * @param  array<string, string>  $catalogo
     * @return list<string>
     */
    public static function nombres(?array $claves, array $catalogo): array
    {
        return array_values(array_map(fn ($c) => $catalogo[$c] ?? $c, array_values(array_filter((array) $claves, fn ($c) => isset($catalogo[$c])))));
    }
}
