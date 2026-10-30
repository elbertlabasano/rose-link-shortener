$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$php = 'D:\xampp\php\php.exe'
$artisan = Join-Path $projectRoot 'artisan'

if (-not (Test-Path -LiteralPath $php -PathType Leaf)) {
    throw "PHP executable was not found at $php"
}

if (-not (Test-Path -LiteralPath $artisan -PathType Leaf)) {
    throw "Laravel Artisan was not found at $artisan"
}

Set-Location -LiteralPath $projectRoot

# Keep the origin private; Cloudflare Tunnel is the only public entry point.
& $php $artisan serve --host=127.0.0.1 --port=8012
exit $LASTEXITCODE
