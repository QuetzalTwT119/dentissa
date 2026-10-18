<?php

$publicPath = realpath(__DIR__.'/public');
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$segments = explode('/', trim($uri, '/'));
$hasTraversal = in_array('..', $segments, true) || in_array('.', $segments, true);
$staticExtensions = ['avif', 'css', 'gif', 'ico', 'jpeg', 'jpg', 'js', 'mjs', 'otf', 'png', 'svg', 'ttf', 'webp', 'woff', 'woff2'];
$mimeTypes = [
    'avif' => 'image/avif',
    'css' => 'text/css; charset=UTF-8',
    'gif' => 'image/gif',
    'ico' => 'image/x-icon',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'js' => 'application/javascript; charset=UTF-8',
    'mjs' => 'application/javascript; charset=UTF-8',
    'otf' => 'font/otf',
    'png' => 'image/png',
    'svg' => 'image/svg+xml',
    'ttf' => 'font/ttf',
    'webp' => 'image/webp',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
];
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

require_once $publicPath.'/index.php';
