<?php

$publicPath = realpath(__DIR__.'/public');
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$segments = explode('/', trim($uri, '/'));
$hasTraversal = in_array('..', $segments, true) || in_array('.', $segments, true);
// Solo se sirven directamente las extensiones de esta lista; cualquier otro
// archivo de public/ (.php, .htaccess, build/manifest.json…) pasa a Laravel.
$mimeTypes = [
    'avif' => 'image/avif',
    'css' => 'text/css; charset=UTF-8',
    'gif' => 'image/gif',
    'html' => 'text/html; charset=UTF-8',
    'ico' => 'image/x-icon',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'js' => 'application/javascript; charset=UTF-8',
    'mjs' => 'application/javascript; charset=UTF-8',
    'otf' => 'font/otf',
    'png' => 'image/png',
    'svg' => 'image/svg+xml',
    'ttf' => 'font/ttf',
    'txt' => 'text/plain; charset=UTF-8',
    'webmanifest' => 'application/manifest+json',
    'webp' => 'image/webp',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'xml' => 'application/xml; charset=UTF-8',
];
$staticExtensions = array_keys($mimeTypes);
$requestedFile = $publicPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, ltrim($uri, '/'));
$extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));

if ($uri !== '/' && ! $hasTraversal && in_array($extension, $staticExtensions, true) && is_file($requestedFile)) {
    $isLongLivedAsset = str_starts_with($uri, '/fonts/') || str_starts_with($uri, '/build/assets/');
    $maxAge = $isLongLivedAsset ? 31536000 : 86400;

    header('Content-Type: '.$mimeTypes[$extension]);
    header('Content-Length: '.filesize($requestedFile));
    header('Cache-Control: public, max-age='.$maxAge.($maxAge === 31536000 ? ', immutable' : ''));
    header('Expires: '.gmdate('D, d M Y H:i:s', time() + $maxAge).' GMT');

    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        readfile($requestedFile);
    }

    return true;
}

// Si la ruta coincide con un archivo real que no está en la lista, el servidor
// de PHP toma ese archivo como SCRIPT_NAME y Laravel calcula mal la ruta (muestra
// la portada en vez de un 404). Se fija el script real para que enrute bien.
$_SERVER['SCRIPT_FILENAME'] = $publicPath.DIRECTORY_SEPARATOR.'index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require_once $publicPath.'/index.php';
