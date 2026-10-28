#Requires -Version 5.1
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

try {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        $scriptArgument = '"' + $PSCommandPath + '"'
        $process = Start-Process -FilePath 'powershell.exe' -Verb RunAs -WindowStyle Hidden -Wait -PassThru -ArgumentList @(
            '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $scriptArgument
        )
        if ($process.ExitCode -ne 0) {
            throw 'Domain setup failed or administrator access was declined.'
        }
        Write-Host 'Domain setup completed.' -ForegroundColor Green
        exit 0
    }

    $wslConfigPath = Join-Path $env:USERPROFILE '.wslconfig'
    $mirroredNetworking = (Test-Path $wslConfigPath) -and (([IO.File]::ReadAllText($wslConfigPath)) -match '(?im)^\s*networkingMode\s*=\s*mirrored\s*$')
    $wslAddress = '127.0.0.1'
    $networkMode = 'mirrored'

    if (-not $mirroredNetworking) {
        $networkMode = 'NAT'
        $wslPath = Join-Path $env:SystemRoot 'System32\wsl.exe'
        $wslOutput = & $wslPath -- hostname -I 2>$null
        if ($LASTEXITCODE -ne 0) {
            throw 'Unable to query WSL2. Start the distribution containing this project and try again.'
        }

        $wslAddress = $null
        foreach ($candidate in ($wslOutput -split '\s+')) {
            $parsedAddress = $null
            if ([Net.IPAddress]::TryParse($candidate, [ref]$parsedAddress) -and $parsedAddress.AddressFamily -eq [Net.Sockets.AddressFamily]::InterNetwork) {
                $wslAddress = $parsedAddress.IPAddressToString
                break
            }
        }
        if (-not $wslAddress) {
            throw 'No WSL2 IPv4 address was found. Check the WSL2 network connection and try again.'
        }
    }

    $hostsPath = Join-Path $env:SystemRoot 'System32\drivers\etc\hosts'
    $domains = @('a.base.local', 'p.base.local', 'www.base.local')
    $originalBytes = [IO.File]::ReadAllBytes($hostsPath)
    $hostsText = [IO.File]::ReadAllText($hostsPath)
    $updatedLines = @()

    foreach ($line in ($hostsText -split '\r?\n')) {
        if ($line.Trim() -eq '# Base framework local domains') {
            continue
        }

        $entry = ($line -split '#', 2)[0].Trim()
        if (-not $entry) {
            $updatedLines += $line
            continue
        }

        $parts = $entry -split '\s+'
        if ($parts.Count -lt 2) {
            $updatedLines += $line
            continue
        }

        $remainingDomains = @($parts[1..($parts.Count - 1)] | Where-Object { $domains -notcontains $_ })
        if ($remainingDomains.Count -eq $parts.Count - 1) {
            $updatedLines += $line
        } elseif ($remainingDomains.Count -gt 0) {
            $updatedLines += $parts[0] + ' ' + ($remainingDomains -join ' ')
        }
    }

    while ($updatedLines.Count -gt 0 -and $updatedLines[-1].Trim() -eq '') {
        if ($updatedLines.Count -eq 1) {
            $updatedLines = @()
        } else {
            $updatedLines = @($updatedLines[0..($updatedLines.Count - 2)])
        }
    }
    $updatedLines += ''
    $updatedLines += '# Base framework local domains'
    $updatedLines += $wslAddress + ' ' + ($domains -join ' ')
    $updatedText = ($updatedLines -join "`r`n") + "`r`n"

    if ($updatedText -cne $hostsText) {
        $backupPath = $hostsPath + '.base-backup-' + [Guid]::NewGuid().ToString('N')
        [IO.File]::WriteAllBytes($backupPath, $originalBytes)
        [IO.File]::WriteAllText($hostsPath, $updatedText, (New-Object Text.UTF8Encoding($false)))
        Write-Host "Hosts backup: $backupPath"
    }

    & "$env:SystemRoot\System32\ipconfig.exe" /flushdns
    if ($LASTEXITCODE -ne 0) {
        throw 'Hosts entries are configured, but DNS cache refresh failed.'
    }

    Write-Host "WSL2 network: $networkMode; address: $wslAddress" -ForegroundColor Cyan
    Write-Host 'Configured: a.base.local, p.base.local, www.base.local' -ForegroundColor Green
    Write-Host 'Backend: http://a.base.local/login'
    Write-Host 'Partner and frontend routes are not implemented yet.'
    Write-Host 'Run this script again after WSL2 restarts if its address changes.'
    exit 0
} catch {
    [Console]::Error.WriteLine($_.Exception.Message)
    exit 1
}
