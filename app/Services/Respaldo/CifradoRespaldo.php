<?php

namespace App\Services\Respaldo;

use RuntimeException;

/**
 * Cifra y descifra archivos de respaldo con XChaCha20-Poly1305 (libsodium,
 * secretstream). Cada bloque lleva su propia etiqueta de autenticación: si
 * alguien altera un solo byte o corta el archivo, descifrar falla.
 *
 * Formato: "DNTB1" + cabecera de 24 bytes + bloques [longitud uint32 + datos].
 * La llave (32 bytes en base64) vive solo en DENTISSA_RESPALDO_CLAVE.
 */
class CifradoRespaldo
{
    private const MAGIA = 'DNTB1';

    private const BLOQUE = 1024 * 1024;

    public function cifrar(string $origen, string $destino): void
    {
        $llave = $this->llave();
        $entrada = $this->abrir($origen, 'rb');
        $salida = $this->abrir($destino, 'wb');

        try {
            [$estado, $cabecera] = sodium_crypto_secretstream_xchacha20poly1305_init_push($llave);
            fwrite($salida, self::MAGIA.$cabecera);

            do {
                $bloque = fread($entrada, self::BLOQUE);
                $ultimo = feof($entrada);
                $etiqueta = $ultimo
                    ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                    : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
                $cifrado = sodium_crypto_secretstream_xchacha20poly1305_push($estado, (string) $bloque, '', $etiqueta);
                fwrite($salida, pack('N', strlen($cifrado)).$cifrado);
            } while (! $ultimo);
        } finally {
            fclose($entrada);
            fclose($salida);
            sodium_memzero($llave);
        }
    }

    public function descifrar(string $origen, string $destino): void
    {
        $llave = $this->llave();
        $entrada = $this->abrir($origen, 'rb');
        $salida = $this->abrir($destino, 'wb');

        try {
            if (fread($entrada, strlen(self::MAGIA)) !== self::MAGIA) {
                throw new RuntimeException('El archivo no es un respaldo de Dentissa.');
            }

            $cabecera = fread($entrada, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            if ($cabecera === false || strlen($cabecera) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
                throw new RuntimeException('El respaldo está incompleto: falta la cabecera de cifrado.');
            }
            $estado = sodium_crypto_secretstream_xchacha20poly1305_init_pull($cabecera, $llave);
            $final = false;

            while (! $final) {
                $longitud = fread($entrada, 4);
                if ($longitud === false || strlen($longitud) !== 4) {
                    throw new RuntimeException('El respaldo está incompleto: falta el final del archivo.');
                }

                $bloque = fread($entrada, unpack('N', $longitud)[1]);
                $resultado = sodium_crypto_secretstream_xchacha20poly1305_pull($estado, (string) $bloque);
                if ($resultado === false) {
                    throw new RuntimeException('No se pudo descifrar el respaldo: la llave no coincide o el archivo fue alterado.');
                }

                [$claro, $etiqueta] = $resultado;
                fwrite($salida, $claro);
                $final = $etiqueta === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
            }

            if (fread($entrada, 1) !== '') {
                throw new RuntimeException('El respaldo tiene datos extra después del final.');
            }
        } finally {
            fclose($entrada);
            fclose($salida);
            sodium_memzero($llave);
        }
    }

    public static function generarLlave(): string
    {
        return base64_encode(sodium_crypto_secretstream_xchacha20poly1305_keygen());
    }

    private function llave(): string
    {
        $configurada = (string) config('dentissa.respaldos.clave');
        $llave = base64_decode($configurada, true);

        if ($configurada === '' || $llave === false || strlen($llave) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
            throw new RuntimeException('Falta DENTISSA_RESPALDO_CLAVE o no es una llave válida de 32 bytes en base64.');
        }

        return $llave;
    }

    /** @return resource */
    private function abrir(string $ruta, string $modo)
    {
        $recurso = @fopen($ruta, $modo);
        if ($recurso === false) {
            throw new RuntimeException('No se pudo abrir el archivo temporal del respaldo.');
        }

        return $recurso;
    }
}
