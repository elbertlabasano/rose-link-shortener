$ErrorActionPreference = 'Stop'

$taskName = 'Rose Link Shortener'
$projectRoot = Split-Path -Parent $PSScriptRoot
$serverScript = Join-Path $PSScriptRoot 'start-shortener.ps1'
$powerShell = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'

if (-not (Test-Path -LiteralPath $serverScript -PathType Leaf)) {
    throw "Startup script was not found at $serverScript"
}

$action = New-ScheduledTaskAction `
    -Execute $powerShell `
    -Argument "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File `"$serverScript`"" `
    -WorkingDirectory $projectRoot

$identity = [System.Security.Principal.WindowsIdentity]::GetCurrent()
$windowsPrincipal = [System.Security.Principal.WindowsPrincipal]::new($identity)
$isAdministrator = $windowsPrincipal.IsInRole(
    [System.Security.Principal.WindowsBuiltInRole]::Administrator
)

if ($isAdministrator) {
    $trigger = New-ScheduledTaskTrigger -AtStartup
    $principal = New-ScheduledTaskPrincipal `
        -UserId 'SYSTEM' `
        -LogonType ServiceAccount `
        -RunLevel Highest
    $startupMode = 'computer startup as SYSTEM'
} else {
    $trigger = New-ScheduledTaskTrigger -AtLogOn -User $identity.Name
    $principal = New-ScheduledTaskPrincipal `
        -UserId $identity.Name `
        -LogonType Interactive `
        -RunLevel Limited
    $startupMode = "sign-in for $($identity.Name)"
}

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -MultipleInstances IgnoreNew

$task = New-ScheduledTask `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Description "Runs the Rose Link Shortener origin on 127.0.0.1:8012 for Cloudflare Tunnel at $startupMode."

Register-ScheduledTask -TaskName $taskName -InputObject $task -Force | Out-Null
Start-ScheduledTask -TaskName $taskName

Write-Output "Installed and started scheduled task: $taskName ($startupMode)"
