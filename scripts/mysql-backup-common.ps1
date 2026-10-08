if ($PSVersionTable.PSVersion.Major -lt 5) {
    throw 'Estos scripts requieren Windows PowerShell 5.1 o PowerShell 7 o posterior.'
}

function ConvertTo-WindowsArgument {
    param([Parameter(Mandatory = $true)][AllowEmptyString()][string] $Value)

    if ($Value.Length -gt 0 -and $Value -notmatch '[\s"]') {
        return $Value
    }

    $builder = [System.Text.StringBuilder]::new()
    [void] $builder.Append('"')
    $backslashes = 0

    for ($index = 0; $index -lt $Value.Length; $index++) {
        $character = $Value[$index]
        if ($character -eq '\') {
            $backslashes++
            continue
        }

        if ($character -eq '"') {
            if ($backslashes -gt 0) {
                [void] $builder.Append(('\' * (2 * $backslashes)))
            }
            [void] $builder.Append('\"')
            $backslashes = 0
            continue
        }

        if ($backslashes -gt 0) {
            [void] $builder.Append(('\' * $backslashes))
            $backslashes = 0
        }
        [void] $builder.Append($character)
    }

    if ($backslashes -gt 0) {
        [void] $builder.Append(('\' * (2 * $backslashes)))
    }
    [void] $builder.Append('"')
    return $builder.ToString()
}

function Invoke-DockerCompose {
    param(
        [Parameter(Mandatory = $true)]
        [string[]] $Arguments,

        [string] $StandardOutputPath,

        [System.IO.Stream] $StandardInputStream
    )

    $dockerCommand = Get-Command docker -ErrorAction Stop
    $startInfo = [System.Diagnostics.ProcessStartInfo]::new()
    $startInfo.FileName = $dockerCommand.Path
    $startInfo.UseShellExecute = $false
    $startInfo.RedirectStandardInput = [bool] $StandardInputStream
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $startInfo.Arguments = (($Arguments | ForEach-Object { ConvertTo-WindowsArgument -Value $_ }) -join ' ')

    $process = [System.Diagnostics.Process]::new()
    $process.StartInfo = $startInfo
    if (-not $process.Start()) {
        throw 'No se pudo iniciar Docker CLI.'
    }

    $outputStream = $null
    try {
        $errorTask = $process.StandardError.ReadToEndAsync()

        if ($StandardOutputPath) {
            $outputStream = [System.IO.File]::Create($StandardOutputPath)
            $outputTask = $process.StandardOutput.BaseStream.CopyToAsync($outputStream)
        } else {
            $outputTask = $process.StandardOutput.ReadToEndAsync()
        }

        if ($StandardInputStream) {
            $inputTask = $StandardInputStream.CopyToAsync($process.StandardInput.BaseStream)
            [void] $inputTask.GetAwaiter().GetResult()
            $process.StandardInput.Close()
        }

        $process.WaitForExit()
        [void] $outputTask.GetAwaiter().GetResult()
        if ($outputStream) { $outputStream.Flush() }
        if (-not $StandardOutputPath) { $output = $outputTask.Result }

        $errorOutput = $errorTask.GetAwaiter().GetResult()
        if ($process.ExitCode -ne 0) {
            throw "Docker Compose falló (código $($process.ExitCode)): $errorOutput"
        }

        if (-not $StandardOutputPath) {
            return $output.Trim()
        }
    } finally {
        if ($outputStream) { $outputStream.Dispose() }
        $process.Dispose()
    }
}

function Get-ComposeMysqlVariable {
    param([Parameter(Mandatory = $true)][string] $Name)

    $value = Invoke-DockerCompose -Arguments @(
        'compose', 'exec', '-T', 'mysql', 'printenv', $Name
    )

    if ([string]::IsNullOrWhiteSpace($value)) {
        throw "El contenedor MySQL no devolvió la variable $Name. ¿Está iniciado y saludable?"
    }

    return $value
}

function Invoke-ComposeMysqlRootQuery {
    param([Parameter(Mandatory = $true)][string] $Query)

    return Invoke-DockerCompose -Arguments @(
        'compose', 'exec', '-T', 'mysql', 'sh', '-lc',
        'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql --user=root --batch --skip-column-names --execute="$1"',
        'sh', $Query
    )
}
