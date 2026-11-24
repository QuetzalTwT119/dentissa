<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * AuditoriaService::registrar() exige una transacción abierta. Las pruebas de
 * cada pantalla no lo detectan: RefreshDatabase ya corre cada prueba dentro de
 * una transacción, así que el error solo aparecía en local y en Railway
 * (24/11/2026: bloqueos, consentimientos, recetas, pagos, planes y exploración
 * clínica fallaban con "No se guardaron los cambios").
 *
 * Esta prueba lee el código: toda función que llama a la bitácora debe abrir
 * su propia DB::transaction() (o usar registrarCambio(), que ya la abre).
 */
class BitacoraEnTransaccionTest extends TestCase
{
    public function test_toda_funcion_que_escribe_en_la_bitacora_abre_una_transaccion(): void
    {
        $sinTransaccion = [];

        foreach ((new Finder)->files()->in(dirname(__DIR__, 2).'/app')->name('*.php') as $archivo) {
            foreach ($this->funciones($archivo->getContents()) as $nombre => $cuerpo) {
                $usaBitacora = preg_match('/\$(this->)?auditoria->registrar\(/', $cuerpo) === 1;
                $abreTransaccion = preg_match('/DB::transaction\(|->transaction\(/', $cuerpo) === 1;

                if ($usaBitacora && ! $abreTransaccion) {
                    $sinTransaccion[] = $archivo->getRelativePathname().' → '.$nombre.'()';
                }
            }
        }

        $this->assertSame([], $sinTransaccion,
            "Estas funciones escriben en la bitácora sin DB::transaction() y fallan fuera de las pruebas:\n"
            .implode("\n", $sinTransaccion));
    }

    /**
     * Separa el archivo en funciones (nombre => cuerpo) contando llaves con los
     * tokens de PHP, para no confundirse con llaves dentro de textos.
     *
     * @return array<string, string>
     */
    private function funciones(string $codigo): array
    {
        $tokens = \PhpToken::tokenize($codigo);
        $funciones = [];

        for ($i = 0, $total = count($tokens); $i < $total; $i++) {
            if (! $tokens[$i]->is(T_FUNCTION)) {
                continue;
            }

            // Solo funciones con nombre (las closures quedan dentro de su función).
            $j = $i + 1;
            while ($j < $total && $tokens[$j]->is(T_WHITESPACE)) {
                $j++;
            }
            if ($j >= $total || ! $tokens[$j]->is(T_STRING)) {
                continue;
            }
            $nombre = $tokens[$j]->text;

            // Avanza hasta la llave que abre el cuerpo (las abstractas no tienen).
            while ($j < $total && $tokens[$j]->text !== '{' && $tokens[$j]->text !== ';') {
                $j++;
            }
            if ($j >= $total || $tokens[$j]->text === ';') {
                continue;
            }

            $profundidad = 0;
            $cuerpo = '';
            for (; $j < $total; $j++) {
                $texto = $tokens[$j]->text;
                $cuerpo .= $texto;
                if ($texto === '{' || $tokens[$j]->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                    $profundidad++;
                } elseif ($texto === '}') {
                    $profundidad--;
                    if ($profundidad === 0) {
                        break;
                    }
                }
            }

            $funciones[$nombre] = ($funciones[$nombre] ?? '').$cuerpo;
            $i = $j;
        }

        return $funciones;
    }
}
