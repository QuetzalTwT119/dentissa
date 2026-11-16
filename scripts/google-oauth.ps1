$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$privateDirectory = Join-Path $projectRoot 'storage/app/private'
$clientFile = Join-Path $privateDirectory 'google-oauth-client.json'
$tokenFile = Join-Path $privateDirectory 'google-oauth-token.json'
$redirectUri = 'http://localhost:53682/'
# gmail.send: enviar correos. drive.file: subir respaldos cifrados; solo da
# acceso a los archivos que crea Dentissa, no al resto del Drive.
$scope = 'https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/drive.file'

if (-not (Test-Path -LiteralPath $clientFile -PathType Leaf)) {
    throw 'No se encontró storage/app/private/google-oauth-client.json.'
}

$clientDocument = Get-Content -LiteralPath $clientFile -Raw | ConvertFrom-Json
$client = $clientDocument.installed

if (-not $client -or -not $client.client_id -or -not $client.client_secret) {
    throw 'El JSON no parece ser una credencial OAuth de aplicación de escritorio.'
}

$state = [Guid]::NewGuid().ToString('N')
$listener = [System.Net.HttpListener]::new()
$listener.Prefixes.Add($redirectUri)
$listener.Start()

try {
    $parameters = [ordered]@{
        client_id     = $client.client_id
        redirect_uri  = $redirectUri
        response_type = 'code'
        scope         = $scope
        access_type   = 'offline'
        prompt        = 'consent'
        state         = $state
    }
    $query = ($parameters.GetEnumerator() | ForEach-Object {
        '{0}={1}' -f [Uri]::EscapeDataString($_.Key), [Uri]::EscapeDataString([string]$_.Value)
    }) -join '&'
    $authorizationUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' + $query

    Write-Host 'Se abrirá Google para autorizar el Gmail emisor y la carpeta de respaldos en Drive. Mantén esta ventana abierta.'
    Start-Process $authorizationUrl

    $context = $listener.GetContext()
    $request = $context.Request
    $response = $context.Response

    if ($request.QueryString['state'] -ne $state) {
        throw 'La respuesta OAuth no coincidió con la solicitud iniciada.'
    }

    if ($request.QueryString['error']) {
        throw 'Google canceló o rechazó la autorización.'
    }

    $authorizationCode = $request.QueryString['code']
    if (-not $authorizationCode) {
        throw 'Google no devolvió un código de autorización.'
    }

    try {
        $tokenResponse = Invoke-RestMethod -Method Post -Uri 'https://oauth2.googleapis.com/token' -ContentType 'application/x-www-form-urlencoded' -Body @{
            code          = $authorizationCode
            client_id     = $client.client_id
            client_secret = $client.client_secret
            redirect_uri  = $redirectUri
            grant_type    = 'authorization_code'
        }
    }
    catch {
        throw 'Google no pudo completar el intercambio OAuth. Revisa la configuración del cliente y vuelve a intentarlo.'
    }

    if (-not $tokenResponse.refresh_token) {
        throw 'Google no entregó un refresh token. Revoca el acceso de Dentissa en tu cuenta Google e inténtalo otra vez.'
    }

    $tokenDocument = [ordered]@{
        refresh_token = $tokenResponse.refresh_token
        scope         = $tokenResponse.scope
        token_type    = $tokenResponse.token_type
        created_at_utc = [DateTime]::UtcNow.ToString('o')
    } | ConvertTo-Json

    $utf8WithoutBom = [System.Text.UTF8Encoding]::new($false)
    [System.IO.File]::WriteAllText($tokenFile, $tokenDocument, $utf8WithoutBom)

    $responseText = '<!doctype html><html lang="es"><meta charset="utf-8"><title>Dentissa</title><body><h2>Autorización completada</h2><p>Ya puedes cerrar esta pestaña y volver a PowerShell.</p></body></html>'
    $responseBytes = [System.Text.Encoding]::UTF8.GetBytes($responseText)
    $response.ContentType = 'text/html; charset=utf-8'
    $response.ContentLength64 = $responseBytes.Length
    $response.OutputStream.Write($responseBytes, 0, $responseBytes.Length)
    $response.Close()

    Write-Host 'Autorización completada. El refresh token quedó guardado en storage/app/private y no se mostró en pantalla.'
}
finally {
    if ($listener.IsListening) {
        $listener.Stop()
    }
    $listener.Close()
}
