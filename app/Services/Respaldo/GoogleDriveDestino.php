<?php

namespace App\Services\Respaldo;

use App\Services\Google\GoogleAccessToken;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Respaldos en una carpeta de Google Drive, con la API REST v3.
 *
 * Usa el permiso `drive.file`: Dentissa solo ve y borra los archivos que
 * ella misma creó, nunca el resto del Drive de la clínica. Los archivos
 * llegan ya cifrados; Google no puede leer su contenido.
 */
class GoogleDriveDestino implements DestinoRespaldo
{
    private const API = 'https://www.googleapis.com/drive/v3';

    private const SUBIDA = 'https://www.googleapis.com/upload/drive/v3/files';

    private const TIPO_CARPETA = 'application/vnd.google-apps.folder';

    public function __construct(private GoogleAccessToken $token) {}

    public function subir(string $rutaLocal, string $nombre): string
    {
        // Subida reanudable: sirve para archivos grandes (imágenes clínicas)
        // sin cargarlos completos en memoria.
        $inicio = $this->cliente()
            ->withHeaders(['X-Upload-Content-Type' => 'application/octet-stream', 'X-Upload-Content-Length' => (string) filesize($rutaLocal)])
            ->post(self::SUBIDA.'?uploadType=resumable', [
                'name' => $nombre,
                'parents' => [$this->carpeta()],
                'mimeType' => 'application/octet-stream',
            ]);
        $this->exigir($inicio, 'iniciar la subida');

        $sesion = $inicio->header('Location');
        if (! $sesion) {
            throw new RuntimeException('Google Drive no devolvió la dirección de subida del respaldo.');
        }

        // El stream de Guzzle cierra el archivo al liberarse.
        $respuesta = $this->cliente()
            ->timeout(600)
            ->withBody(Utils::streamFor(fopen($rutaLocal, 'rb')), 'application/octet-stream')
            ->put($sesion);
        $this->exigir($respuesta, 'subir el respaldo');

        return (string) $respuesta->json('id');
    }

    public function listar(): array
    {
        $respuesta = $this->cliente()->get(self::API.'/files', [
            'q' => "'{$this->carpeta()}' in parents and trashed = false",
            'orderBy' => 'createdTime desc',
            'fields' => 'files(id,name,createdTime,size)',
            'pageSize' => 1000,
            'spaces' => 'drive',
        ]);
        $this->exigir($respuesta, 'listar los respaldos');

        return array_map(fn (array $archivo) => [
            'id' => $archivo['id'],
            'nombre' => $archivo['name'],
            'creado' => Carbon::parse($archivo['createdTime']),
            'bytes' => (int) ($archivo['size'] ?? 0),
        ], $respuesta->json('files') ?? []);
    }

    public function descargar(string $id, string $rutaLocal): void
    {
        $respuesta = $this->cliente()->timeout(600)->sink($rutaLocal)
            ->get(self::API.'/files/'.rawurlencode($id), ['alt' => 'media']);
        $this->exigir($respuesta, 'descargar el respaldo');
    }

    public function eliminar(string $id): void
    {
        $respuesta = $this->cliente()->delete(self::API.'/files/'.rawurlencode($id));
        $this->exigir($respuesta, 'eliminar un respaldo antiguo');
    }

    /** Busca la carpeta de respaldos o la crea la primera vez. */
    private function carpeta(): string
    {
        $nombre = (string) config('dentissa.respaldos.carpeta_drive');

        return Cache::remember('dentissa.respaldos.carpeta:'.md5($nombre), now()->addDay(), function () use ($nombre) {
            $escapado = str_replace(['\\', "'"], ['\\\\', "\\'"], $nombre);
            $busqueda = $this->cliente()->get(self::API.'/files', [
                'q' => "mimeType = '".self::TIPO_CARPETA."' and name = '{$escapado}' and trashed = false",
                'fields' => 'files(id)',
                'spaces' => 'drive',
            ]);
            $this->exigir($busqueda, 'buscar la carpeta de respaldos');

            if ($id = $busqueda->json('files.0.id')) {
                return $id;
            }

            $creada = $this->cliente()->post(self::API.'/files', ['name' => $nombre, 'mimeType' => self::TIPO_CARPETA]);
            $this->exigir($creada, 'crear la carpeta de respaldos');

            return (string) $creada->json('id');
        });
    }

    private function cliente(): PendingRequest
    {
        return Http::withToken($this->token->obtener())->acceptJson()->timeout(60);
    }

    private function exigir(Response $respuesta, string $accion): void
    {
        if ($respuesta->failed()) {
            // Solo el código HTTP: el cuerpo puede traer datos de la cuenta.
            $pista = $respuesta->status() === 403
                ? ' Revisa que la cuenta de Google se autorizara con el permiso de Drive (scripts/google-oauth.ps1).'
                : '';

            throw new RuntimeException("Google Drive no pudo {$accion} (HTTP {$respuesta->status()}).{$pista}");
        }
    }
}
