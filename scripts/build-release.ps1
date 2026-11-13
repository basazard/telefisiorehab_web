<#
 scripts/build-release.ps1 (PRD 11 E7)

 Builds the production files from the latest main on GitHub, on YOUR PC:
   - vendor/        (composer install --no-dev)
   - public/build/  (npm run build)
   - RELEASE_COMMIT (the exact commit built; scripts/deploy.sh refuses the archive unless the server's origin/main is that commit)
 and packs them into one archive, release.tar.gz, ready to upload to the server.
 It works in a temporary clean copy, so your own project folder is not touched.

 Usage (PowerShell, any folder):   .\scripts\build-release.ps1
 Needs: git, composer, node/npm and tar (tar is built into Windows 10 and 11).
#>
param(
    [string]$Repo   = 'https://github.com/basazard/telefisiorehab_web.git',
    [string]$Branch = 'main',
    [string]$Out    = (Join-Path ([Environment]::GetFolderPath('Desktop')) 'release.tar.gz')
)

$ErrorActionPreference = 'Stop'

foreach ($tool in 'git', 'composer', 'npm', 'tar') {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) { throw "$tool is not installed or not in PATH" }
}

$work = Join-Path ([System.IO.Path]::GetTempPath()) 'tfr-release'
if (Test-Path $work) { Remove-Item $work -Recurse -Force }

Write-Host "==> Cloning $Repo ($Branch)"
git clone --depth 1 --branch $Branch $Repo $work
if ($LASTEXITCODE -ne 0) { throw 'git clone failed' }

Push-Location $work
try {
    $commit = (git rev-parse HEAD).Trim()

    Write-Host '==> composer install --no-dev'
    composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'composer install failed' }

    Write-Host '==> npm ci'
    npm ci
    if ($LASTEXITCODE -ne 0) { throw 'npm ci failed' }

    Write-Host '==> npm run build'
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }

    if (-not (Test-Path 'vendor/autoload.php'))        { throw 'vendor/autoload.php was not created' }
    if (-not (Test-Path 'public/build/manifest.json')) { throw 'public/build/manifest.json was not created' }

    # the commit this archive belongs to: plain ASCII, no newline, so the server reads it exactly
    [System.IO.File]::WriteAllText((Join-Path $work 'RELEASE_COMMIT'), $commit, [System.Text.Encoding]::ASCII)

    if (Test-Path $Out) { Remove-Item $Out -Force }
    tar -czf $Out vendor public/build RELEASE_COMMIT
    if ($LASTEXITCODE -ne 0) { throw 'tar failed' }

    $mb = [math]::Round((Get-Item $Out).Length / 1MB, 1)
    Write-Host ''
    Write-Host "Done. Release of commit $($commit.Substring(0, 7)) ($mb MB): $Out"
    Write-Host 'Next: upload it to the app folder on the server and run, for example:'
    Write-Host '  bash scripts/deploy.sh staging release.tar.gz'
    Write-Host '  bash scripts/deploy.sh production release.tar.gz'
    Write-Host 'Deploy before anything new is pushed to main: the server refuses an archive of an older commit.'
}
finally {
    Pop-Location
}
