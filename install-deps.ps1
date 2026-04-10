# MedFlow CRM - Dependency Installer
# Run as Administrator in PowerShell 5.1

Write-Host ""
Write-Host "MedFlow CRM - Installing dependencies via winget" -ForegroundColor Cyan
Write-Host "PHP 8.2 + Composer + Node.js (no Docker, no Laragon)" -ForegroundColor Cyan
Write-Host ""

# Check winget
if (-not (Get-Command winget -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: winget not found." -ForegroundColor Red
    Write-Host "Open Microsoft Store, search 'App Installer', and update it." -ForegroundColor Yellow
    exit 1
}

Write-Host "winget found." -ForegroundColor Green

# Install PHP 8.2
Write-Host ""
Write-Host ">>> Installing PHP 8.2..." -ForegroundColor Cyan
if (Get-Command php -ErrorAction SilentlyContinue) {
    Write-Host "    PHP already installed - skipping" -ForegroundColor Green
} else {
    winget install --id PHP.PHP.8.2 --silent --accept-package-agreements --accept-source-agreements
    Write-Host "    PHP install command sent" -ForegroundColor Green
}

# Install Composer
Write-Host ""
Write-Host ">>> Installing Composer..." -ForegroundColor Cyan
if (Get-Command composer -ErrorAction SilentlyContinue) {
    Write-Host "    Composer already installed - skipping" -ForegroundColor Green
} else {
    winget install --id Composer.Composer --silent --accept-package-agreements --accept-source-agreements
    Write-Host "    Composer install command sent" -ForegroundColor Green
}

# Install Node.js LTS
Write-Host ""
Write-Host ">>> Installing Node.js LTS..." -ForegroundColor Cyan
if (Get-Command node -ErrorAction SilentlyContinue) {
    Write-Host "    Node.js already installed - skipping" -ForegroundColor Green
} else {
    winget install --id OpenJS.NodeJS.LTS --silent --accept-package-agreements --accept-source-agreements
    Write-Host "    Node.js install command sent" -ForegroundColor Green
}

# Refresh PATH for this session
Write-Host ""
Write-Host ">>> Refreshing PATH..." -ForegroundColor Cyan
$machinePath = [System.Environment]::GetEnvironmentVariable("Path", "Machine")
$userPath    = [System.Environment]::GetEnvironmentVariable("Path", "User")
$env:Path    = $machinePath + ";" + $userPath

# Configure PHP extensions
Write-Host ""
Write-Host ">>> Configuring PHP extensions..." -ForegroundColor Cyan

$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if ($phpCmd) {
    $phpDir  = Split-Path $phpCmd.Source
    $iniDest = Join-Path $phpDir "php.ini"
    $iniSrc  = Join-Path $phpDir "php.ini-development"

    if ((-not (Test-Path $iniDest)) -and (Test-Path $iniSrc)) {
        Copy-Item $iniSrc $iniDest
        Write-Host "    Created php.ini from php.ini-development" -ForegroundColor Green
    }

    if (Test-Path $iniDest) {
        $ini = Get-Content $iniDest -Raw

        $exts = @(
            "pdo_mysql",
            "pdo_sqlite",
            "mbstring",
            "openssl",
            "tokenizer",
            "xml",
            "ctype",
            "bcmath",
            "fileinfo",
            "curl",
            "gd",
            "zip"
        )

        foreach ($ext in $exts) {
            if ($ini -match ";extension=$ext") {
                $ini = $ini -replace ";extension=$ext", "extension=$ext"
                Write-Host "    Enabled: $ext" -ForegroundColor Green
            }
        }

        Set-Content $iniDest $ini
        Write-Host "    php.ini saved" -ForegroundColor Green
    } else {
        Write-Host "    php.ini not found - extensions may need manual enabling" -ForegroundColor Yellow
    }
} else {
    Write-Host "    PHP not in PATH yet - extensions will be configured after restart" -ForegroundColor Yellow
}

# Verify
Write-Host ""
Write-Host ">>> Verifying installations..." -ForegroundColor Cyan

$allGood = $true

if (Get-Command php -ErrorAction SilentlyContinue) {
    $v = php -r "echo PHP_VERSION;"
    Write-Host "    PHP $v" -ForegroundColor Green
} else {
    Write-Host "    PHP - not visible yet" -ForegroundColor Yellow
    $allGood = $false
}

if (Get-Command composer -ErrorAction SilentlyContinue) {
    Write-Host "    Composer - OK" -ForegroundColor Green
} else {
    Write-Host "    Composer - not visible yet" -ForegroundColor Yellow
    $allGood = $false
}

if (Get-Command node -ErrorAction SilentlyContinue) {
    $v = node --version
    Write-Host "    Node.js $v" -ForegroundColor Green
} else {
    Write-Host "    Node.js - not visible yet" -ForegroundColor Yellow
    $allGood = $false
}

Write-Host ""
if ($allGood) {
    Write-Host "All dependencies ready!" -ForegroundColor Green
    Write-Host ""
    Write-Host "CLOSE this terminal, open a NEW PowerShell window, then run:" -ForegroundColor Cyan
    Write-Host "    .\setup.ps1" -ForegroundColor White
} else {
    Write-Host "Some tools were installed but need a terminal restart to appear." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "CLOSE this terminal, open a NEW PowerShell window, then run:" -ForegroundColor Cyan
    Write-Host "    .\setup.ps1" -ForegroundColor White
}

Write-Host ""
