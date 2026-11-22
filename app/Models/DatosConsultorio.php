<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Datos de contacto del consultorio que la doctora cambia desde el panel
 * (22/11/2026): teléfono, WhatsApp de contacto, correo y dirección.
 *
 * Es una sola fila. Lo guardado se aplica sobre config('dentissa.contacto') al
 * arrancar (AppServiceProvider), así la portada, el pie, "¿Prefieres llamar?",
 * la página del recordatorio y la respuesta automática del bot lo usan sin
 * cambios. Un campo vacío conserva el valor de las variables de entorno.
 *
 * No incluye el número del bot de WhatsApp: ese vive en Meta y se configura
 * con DENTISSA_WHATSAPP_PHONE_ID.
 */
#[Fillable(['telefono', 'whatsapp', 'correo', 'direccion'])]
class DatosConsultorio extends Model
{
    protected $table = 'datos_consultorio';

    private const CACHE = 'datos_consultorio';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE));
    }

    /** La fila única (se crea vacía la primera vez que se edita). */
    public static function fila(): self
    {
        return self::query()->firstOrNew();
    }

    /**
     * Valores guardados, en caché para no consultar la base en cada página.
     *
     * @return array{telefono?: string|null, whatsapp?: string|null, correo?: string|null, direccion?: string|null}
     */
    public static function guardados(): array
    {
        return Cache::rememberForever(self::CACHE, fn () => self::query()->first()?->only(['telefono', 'whatsapp', 'correo', 'direccion']) ?? []);
    }

    /**
     * Pone lo guardado encima de config('dentissa.contacto'). Si la tabla aún
     * no existe (antes de migrar) o la base no responde, se queda lo de .env.
     */
    public static function aplicarAConfiguracion(): void
    {
        try {
            $datos = array_filter(self::guardados(), fn ($valor) => filled($valor));
        } catch (Throwable) {
            return;
        }

        if ($datos === []) {
            return;
        }

        $contacto = config('dentissa.contacto');

        if (isset($datos['telefono'])) {
            $contacto['telefono'] = $datos['telefono'];
            $contacto['telefono_enlace'] = 'tel:+'.self::conLada($datos['telefono']);
            // Sin WhatsApp propio, el de contacto es el mismo celular.
            $contacto['whatsapp'] = $datos['whatsapp'] ?? self::conLada($datos['telefono']);
        } elseif (isset($datos['whatsapp'])) {
            $contacto['whatsapp'] = $datos['whatsapp'];
        }

        foreach (['correo', 'direccion'] as $campo) {
            if (isset($datos[$campo])) {
                $contacto[$campo] = $datos[$campo];
            }
        }

        config(['dentissa.contacto' => $contacto]);
    }

    /** "755 113 1833" → "527551131833". */
    public static function conLada(string $telefono): string
    {
        $digitos = preg_replace('/\D/', '', $telefono);

        return strlen($digitos) === 10 ? '52'.$digitos : $digitos;
    }

    /** "7551131833" → "755 113 1833" (como se lee en México). */
    public static function formatear(string $digitos): string
    {
        return preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '$1 $2 $3', $digitos);
    }
}
