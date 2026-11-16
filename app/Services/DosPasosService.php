<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Verificación en dos pasos (TOTP, RFC 6238) del personal.
 *
 * El secreto se guarda cifrado con APP_KEY. Los códigos de recuperación se
 * guardan solo como hash SHA-256: tienen 10 caracteres aleatorios, así que un
 * hash rápido basta y la comparación es en tiempo constante.
 */
class DosPasosService
{
    public const CODIGOS_RECUPERACION = 8;

    public function __construct(private Google2FA $google2fa)
    {
        // Acepta el código anterior y el siguiente (±30 s) por relojes desfasados.
        $this->google2fa->setWindow(1);
    }

    public function nuevoSecreto(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /** Código QR en SVG para escanear con la app de autenticación. */
    public function qrSvg(User $usuario, string $secreto): string
    {
        $uri = $this->google2fa->getQRCodeUrl(
            (string) config('dentissa.seguridad.emisor_dos_pasos'),
            $usuario->email,
            $secreto,
        );

        return (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)))->writeString($uri);
    }

    /**
     * Comprueba un código contra el secreto pendiente (al activar). Devuelve
     * el intervalo aceptado o null.
     */
    public function verificarPendiente(string $secreto, string $codigo): ?int
    {
        $paso = $this->google2fa->verifyKeyNewer($secreto, $this->limpiar($codigo), 0);

        return is_int($paso) ? $paso : null;
    }

    /**
     * Activa la verificación y devuelve los códigos de recuperación en claro.
     * Es la única vez que se muestran.
     *
     * @return list<string>
     */
    public function activar(User $usuario, string $secreto, int $paso): array
    {
        $usuario->forceFill([
            'dos_pasos_secreto' => $secreto,
            'dos_pasos_confirmado_en' => now(),
            'dos_pasos_ultimo_paso' => $paso,
        ])->save();

        return $this->regenerarCodigos($usuario);
    }

    /**
     * Código de la app o código de recuperación. Un código de la app ya
     * aceptado no vuelve a servir; un código de recuperación se elimina al usarlo.
     */
    public function verificar(User $usuario, string $codigo): bool
    {
        if (! $usuario->tieneDosPasos()) {
            return false;
        }

        $limpio = $this->limpiar($codigo);
        if (preg_match('/^\d{6}$/', $limpio)) {
            $paso = $this->google2fa->verifyKeyNewer($usuario->dos_pasos_secreto, $limpio, (int) $usuario->dos_pasos_ultimo_paso);
            if (! is_int($paso)) {
                return false;
            }
            $usuario->forceFill(['dos_pasos_ultimo_paso' => $paso])->save();

            return true;
        }

        return $this->usarCodigoRecuperacion($usuario, $codigo);
    }

    /** @return list<string> */
    public function regenerarCodigos(User $usuario): array
    {
        $codigos = [];
        for ($i = 0; $i < self::CODIGOS_RECUPERACION; $i++) {
            $codigos[] = Str::upper(Str::random(5).'-'.Str::random(5));
        }

        $usuario->forceFill([
            'dos_pasos_recuperacion' => array_map(fn ($c) => $this->hashCodigo($c), $codigos),
        ])->save();

        return $codigos;
    }

    public function codigosRestantes(User $usuario): int
    {
        return count($usuario->dos_pasos_recuperacion ?? []);
    }

    public function desactivar(User $usuario): void
    {
        $usuario->forceFill([
            'dos_pasos_secreto' => null,
            'dos_pasos_recuperacion' => null,
            'dos_pasos_confirmado_en' => null,
            'dos_pasos_ultimo_paso' => null,
        ])->save();
    }

    private function usarCodigoRecuperacion(User $usuario, string $codigo): bool
    {
        $buscado = $this->hashCodigo($codigo);
        $restantes = $usuario->dos_pasos_recuperacion ?? [];

        foreach ($restantes as $indice => $hash) {
            if (hash_equals($hash, $buscado)) {
                unset($restantes[$indice]);
                $usuario->forceFill(['dos_pasos_recuperacion' => array_values($restantes)])->save();

                return true;
            }
        }

        return false;
    }

    private function hashCodigo(string $codigo): string
    {
        return hash('sha256', Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $codigo)));
    }

    private function limpiar(string $codigo): string
    {
        return preg_replace('/\s+/', '', $codigo);
    }
}
