<?php

namespace App\Models;

use App\Enums\EstadoCita;
use App\Enums\EstadoOferta;
use App\Enums\TipoOferta;
use App\Enums\TipoValorOferta;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'titulo', 'descripcion', 'id_tratamiento', 'tipo', 'valor_descuento', 'tipo_valor',
    'precio_especial', 'fecha_inicio', 'fecha_fin', 'cupo_total', 'cupos_utilizados',
    'imagen', 'estado',
])]
class Oferta extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoOferta::class,
            'tipo_valor' => TipoValorOferta::class,
            'valor_descuento' => 'decimal:2',
            'precio_especial' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'estado' => EstadoOferta::class,
        ];
    }

    /**
     * "¿Qué ofreces?" (21/11/2026): antes el formulario pedía 4 campos que se
     * contradecían (tipo, tipo de valor, descuento y precio especial). Ahora es
     * una sola elección + una cantidad; se guarda en las mismas columnas.
     */
    public const BENEFICIOS = [
        'descuento' => 'Descuento',
        'precio' => 'Precio especial',
        'gratis' => 'Gratis',
    ];

    /** Unidad del descuento: se elige junto a la cantidad (21/11/2026, antes eran 2 botones aparte). */
    public const UNIDADES = [
        'porcentaje' => '%',
        'monto' => '$',
    ];

    /** Beneficio del formulario (descuento + unidad, precio o gratis) → clave interna de beneficio(). */
    public static function claveDeFormulario(string $beneficio, ?string $unidad): string
    {
        return $beneficio === 'descuento' ? ($unidad === 'monto' ? 'monto' : 'porcentaje') : $beneficio;
    }

    /** Columnas que corresponden a un beneficio elegido en el formulario. */
    public static function columnasDeBeneficio(string $beneficio, ?float $cantidad): array
    {
        return match ($beneficio) {
            'porcentaje' => ['tipo' => TipoOferta::Descuento, 'tipo_valor' => TipoValorOferta::Porcentaje, 'valor_descuento' => $cantidad, 'precio_especial' => null],
            'monto' => ['tipo' => TipoOferta::Descuento, 'tipo_valor' => TipoValorOferta::MontoFijo, 'valor_descuento' => $cantidad, 'precio_especial' => null],
            'precio' => ['tipo' => TipoOferta::Paquete, 'tipo_valor' => TipoValorOferta::MontoFijo, 'valor_descuento' => null, 'precio_especial' => $cantidad],
            default => ['tipo' => TipoOferta::Bonificacion, 'tipo_valor' => TipoValorOferta::MontoFijo, 'valor_descuento' => null, 'precio_especial' => null],
        };
    }

    /** Beneficio de una oferta ya guardada (también las creadas con el formulario anterior). */
    public function beneficio(): string
    {
        return match (true) {
            (float) $this->precio_especial > 0 => 'precio',
            $this->tipo === TipoOferta::Bonificacion => 'gratis',
            (float) $this->valor_descuento > 0 => $this->tipo_valor === TipoValorOferta::Porcentaje ? 'porcentaje' : 'monto',
            default => 'gratis',
        };
    }

    public function cantidadBeneficio(): ?float
    {
        return match ($this->beneficio()) {
            'precio' => (float) $this->precio_especial,
            'porcentaje', 'monto' => (float) $this->valor_descuento,
            default => null,
        };
    }

    /** Texto corto del beneficio: "−20%", "−$300", "Precio especial $2,800", "Gratis". */
    public function etiquetaBeneficio(): string
    {
        $cantidad = $this->cantidadBeneficio();
        $dinero = fn (float $n) => '$'.number_format($n, fmod($n, 1.0) == 0.0 ? 0 : 2);

        return match ($this->beneficio()) {
            'precio' => 'Precio especial '.$dinero($cantidad),
            'porcentaje' => '−'.rtrim(rtrim(number_format($cantidad, 2), '0'), '.').'%',
            'monto' => '−'.$dinero($cantidad),
            default => 'Gratis',
        };
    }

    /**
     * Lo único que ve el público (portada y /ofertas): publicadas por el
     * administrador y dentro de sus fechas. En revisión u ocultas, solo en el panel.
     */
    public function scopePublicadas(Builder $query): Builder
    {
        $hoy = now()->toDateString();

        return $query->where('estado', EstadoOferta::Activa)
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            // Ligada a un tratamiento: solo si se puede agendar (activo, en especialidad activa).
            ->where(fn ($q) => $q->whereNull('id_tratamiento')
                ->orWhereHas('tratamiento', fn ($t) => $t->agendables()));
    }

    /** Cuántas ofertas muestra el carrusel de la portada (8 vistas de 3 en PC). */
    public const MAXIMO_PORTADA = 24;

    /**
     * Las que muestra la portada: publicadas, con lugar y las que vencen
     * primero (22/11/2026: antes se tomaban 12 y DESPUÉS se quitaban las
     * agotadas, así que podían salir menos de 12 aunque hubiera más).
     */
    public static function paraPortada()
    {
        return self::with('tratamiento:id,nombre')
            ->publicadas()
            ->conLugaresUsados()
            ->orderBy('fecha_fin')
            ->orderBy('id')
            ->get()
            ->reject(fn (self $oferta) => $oferta->lugaresQuedan() === 0)
            ->take(self::MAXIMO_PORTADA)
            ->values();
    }

    /**
     * Por qué una oferta PUBLICADA no se ve en la portada ni al agendar; null si
     * sí se ve (22/11/2026: la lista decía "Publicada" aunque su tratamiento
     * estuviera inactivo y no apareciera en ningún lado). $enPortada: ids que
     * muestra la portada (Oferta::paraPortada()), calculado una vez por lista.
     */
    public function motivoNoVisible(array $enPortada): ?string
    {
        if ($this->estado !== EstadoOferta::Activa) {
            return null;
        }

        $tratamiento = $this->tratamiento;
        $agendable = ! $tratamiento || ($tratamiento->activo && ($tratamiento->servicios_activos
            ?? $tratamiento->servicios()->where('activo', true)->count()) > 0);

        return match (true) {
            $this->fecha_fin->lt(today()) => 'Venció el '.$this->fecha_fin->locale('es')->translatedFormat('j \d\e F').'. Cambia «Válida hasta» para volver a mostrarla.',
            $this->fecha_inicio->gt(today()) => 'Empieza el '.$this->fecha_inicio->locale('es')->translatedFormat('j \d\e F').'.',
            ! $agendable => 'Su tratamiento está inactivo. Actívalo en Catálogo o elige otro al editarla.',
            $this->lugaresQuedan() === 0 => 'Ya no le quedan lugares. Sube «Lugares disponibles» para volver a mostrarla.',
            ! in_array($this->id, $enPortada, true) => 'La portada muestra las '.self::MAXIMO_PORTADA.' que vencen primero; aparecerá cuando alguna termine o se oculte.',
            default => null,
        };
    }

    /**
     * Por qué NO se puede publicar; null si sí (22/11/2026). Una sola regla para
     * el botón ✓ de la lista y para OfertaController::publicar(): antes la lista
     * ofrecía ✓ en ofertas que el servidor después rechazaba.
     */
    public function motivoNoPublicable(): ?string
    {
        if ($this->fecha_fin->lt(today())) {
            return 'Ya venció. Cambia «Válida hasta» antes de publicarla.';
        }

        if ($this->id_tratamiento && ! Tratamiento::agendables()->whereKey($this->id_tratamiento)->exists()) {
            return 'Su tratamiento está inactivo. Actívalo en Catálogo o cambia el tratamiento antes de publicarla.';
        }

        return null;
    }

    /** Citas que ocupan un lugar: todas menos canceladas e inasistencias (esas lo liberan). */
    public const ESTADOS_QUE_LIBERAN = [EstadoCita::Cancelada, EstadoCita::NoAsistio];

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_oferta');
    }

    /** Carga lugares_usados en la misma consulta (para listas). */
    public function scopeConLugaresUsados(Builder $query): Builder
    {
        return $query->withCount(['citas as lugares_usados' => fn ($q) => $q->whereNotIn('estado', self::ESTADOS_QUE_LIBERAN)]);
    }

    /** Lugares ocupados por citas reales (21/11/2026; antes un contador que nunca subía). */
    public function lugaresUsados(): int
    {
        return (int) ($this->lugares_usados
            ?? $this->citas()->whereNotIn('estado', self::ESTADOS_QUE_LIBERAN)->count());
    }

    /** Lugares que quedan; null = sin límite. */
    public function lugaresQuedan(): ?int
    {
        return $this->cupo_total > 0 ? max(0, $this->cupo_total - $this->lugaresUsados()) : null;
    }

    /**
     * Oferta que se aplica a una cita nueva de ese tratamiento en esa fecha:
     * publicada, con el tratamiento agendable, la fecha de la cita dentro de su
     * vigencia y con lugar. Si hay varias, la que vence primero.
     */
    public static function aplicableA(int $idTratamiento, Carbon $fechaCita): ?self
    {
        return self::publicadas()
            ->where('id_tratamiento', $idTratamiento)
            ->whereDate('fecha_fin', '>=', $fechaCita->toDateString())
            ->conLugaresUsados()
            ->orderBy('fecha_fin')
            ->get()
            ->first(fn (self $oferta) => $oferta->lugaresQuedan() !== 0);
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'id_tratamiento');
    }
}
