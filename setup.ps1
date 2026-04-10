# MedFlow CRM - Project Setup (native Windows, no virtualization, no Docker)
# Run from the Clinical project folder:  .\setup.ps1

$logFile = "$env:TEMP\medflow-setup.log"
Start-Transcript -Path $logFile -Append
Write-Host "Log file: $logFile" -ForegroundColor DarkGray

# Write files without BOM (PowerShell 5.1 -Encoding UTF8 adds BOM which breaks PHP)
$utf8NoBOM = New-Object System.Text.UTF8Encoding $false
function Save-File {
    param([string]$RelPath, [string]$Content)
    $fullPath = Join-Path (Get-Location).Path $RelPath
    [System.IO.File]::WriteAllText($fullPath, $Content, $utf8NoBOM)
}

Write-Host ""
Write-Host "MedFlow CRM - Project Setup" -ForegroundColor Cyan
Write-Host "SQLite for local dev, no database server required" -ForegroundColor Cyan
Write-Host ""

# --- 0. Check prerequisites ---------------------------------------------------

Write-Host ">>> Checking prerequisites..." -ForegroundColor Cyan

$missing = @()
if (-not (Get-Command php      -ErrorAction SilentlyContinue)) { $missing += "php" }
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { $missing += "composer" }
if (-not (Get-Command node     -ErrorAction SilentlyContinue)) { $missing += "node" }

if ($missing.Count -gt 0) {
    Write-Host ""
    Write-Host "Missing tools: $($missing -join ', ')" -ForegroundColor Red
    Write-Host ""
    Write-Host "Run install-deps.ps1 first (as Administrator):" -ForegroundColor Yellow
    Write-Host "  .\install-deps.ps1"
    Write-Host ""
    Write-Host "Then close this terminal, open a new one, and re-run .\setup.ps1"
    exit 1
}

$phpVer = php -r "echo PHP_VERSION;"
Write-Host "    PHP $phpVer" -ForegroundColor Green
Write-Host "    Composer OK" -ForegroundColor Green
Write-Host "    Node $(node --version)" -ForegroundColor Green

# --- 1. Scaffold Laravel ------------------------------------------------------

Write-Host ""
Write-Host ">>> Scaffolding Laravel project..." -ForegroundColor Cyan

if (Test-Path "artisan") {
    Write-Host "    Laravel already present - skipping" -ForegroundColor Yellow
} else {
    # Scaffold into a temp folder (current folder is not empty so composer won't allow it directly)
    $tempDir = "$env:TEMP\medflow_scaffold"
    if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }

    composer create-project laravel/laravel $tempDir --prefer-dist --no-interaction

    if (-not (Test-Path "$tempDir\artisan")) {
        Write-Host "ERROR: Laravel scaffold failed." -ForegroundColor Red
        exit 1
    }

    # Move all Laravel files into the current directory
    Write-Host "    Moving Laravel files into project folder..." -ForegroundColor Cyan
    Get-ChildItem -Path $tempDir -Force | ForEach-Object {
        $target = Join-Path (Get-Location) $_.Name
        if (Test-Path $target) { Remove-Item $target -Recurse -Force }
        Move-Item $_.FullName $target
    }
    Remove-Item $tempDir -Force -ErrorAction SilentlyContinue

    Write-Host "    Laravel scaffolded OK" -ForegroundColor Green
}

# --- 2. Environment file ------------------------------------------------------

Write-Host ""
Write-Host ">>> Configuring .env for SQLite..." -ForegroundColor Cyan

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
}

$env_content = Get-Content ".env" -Raw
$env_content = $env_content -replace "(?m)^DB_CONNECTION=.*",  "DB_CONNECTION=sqlite"
$env_content = $env_content -replace "(?m)^DB_HOST=.*",        "#DB_HOST=127.0.0.1"
$env_content = $env_content -replace "(?m)^DB_PORT=.*",        "#DB_PORT=3306"
$env_content = $env_content -replace "(?m)^DB_DATABASE=.*",    "#DB_DATABASE=medflow"
$env_content = $env_content -replace "(?m)^DB_USERNAME=.*",    "#DB_USERNAME=medflow"
$env_content = $env_content -replace "(?m)^DB_PASSWORD=.*",    "#DB_PASSWORD=secret"
$env_content = $env_content -replace "(?m)^APP_NAME=.*",       'APP_NAME="MedFlow CRM"'
$env_content = $env_content -replace "(?m)^APP_URL=.*",        "APP_URL=http://localhost:8000"
Set-Content ".env" $env_content

if (-not (Test-Path "database\database.sqlite")) {
    New-Item -ItemType File -Path "database\database.sqlite" | Out-Null
    Write-Host "    SQLite database file created" -ForegroundColor Green
}

Write-Host "    .env configured (SQLite - no database server needed)" -ForegroundColor Green

# --- 3. Directory structure ---------------------------------------------------

Write-Host ""
Write-Host ">>> Creating application directories..." -ForegroundColor Cyan

New-Item -ItemType Directory -Force -Path "app\Http\Controllers\Auth" | Out-Null
New-Item -ItemType Directory -Force -Path "resources\views\auth"      | Out-Null
New-Item -ItemType Directory -Force -Path "resources\views\layouts"   | Out-Null
New-Item -ItemType Directory -Force -Path "resources\views\dashboard" | Out-Null

Write-Host "    Directories ready" -ForegroundColor Green

# --- 4. LoginController -------------------------------------------------------

Write-Host ""
Write-Host ">>> Writing LoginController..." -ForegroundColor Cyan

$loginController = @'
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
'@

Save-File "app\Http\Controllers\Auth\LoginController.php" $loginController
Write-Host "    LoginController written" -ForegroundColor Green

# --- 5. Routes ----------------------------------------------------------------

Write-Host ""
Write-Host ">>> Writing routes/web.php..." -ForegroundColor Cyan

$routes = @'
<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn() => view('dashboard.index'))->name('dashboard');
    Route::post('/logout',   [LoginController::class, 'logout'])->name('logout');
});
'@

Save-File "routes\web.php" $routes
Write-Host "    Routes written" -ForegroundColor Green

# --- 6. Patch users migration -------------------------------------------------

Write-Host ""
Write-Host ">>> Patching users migration (adding role column)..." -ForegroundColor Cyan

$migFile = Get-ChildItem "database\migrations" -Filter "*create_users_table*" | Select-Object -First 1
if ($migFile) {
    $mig = Get-Content $migFile.FullName -Raw
    if ($mig -notmatch "'role'") {
        $mig = $mig -replace "(\`$table->string\('password'\);)", '$1' + "`r`n            `$table->string('role')->default('admin');"
        [System.IO.File]::WriteAllText($migFile.FullName, $mig, $utf8NoBOM)
        Write-Host "    Role column added to $($migFile.Name)" -ForegroundColor Green
    } else {
        Write-Host "    Role column already present" -ForegroundColor Yellow
    }
} else {
    Write-Host "    WARNING: Users migration not found" -ForegroundColor Yellow
}

# --- 7. Patch User model ------------------------------------------------------

Write-Host ""
Write-Host ">>> Patching User model..." -ForegroundColor Cyan

if (Test-Path "app\Models\User.php") {
    $model = Get-Content "app\Models\User.php" -Raw
    if ($model -notmatch "'role'") {
        $model = $model -replace "'email_verified_at'", "'email_verified_at', 'role'"
        Save-File "app\Models\User.php" $model
        Write-Host "    User model patched" -ForegroundColor Green
    } else {
        Write-Host "    User model already patched" -ForegroundColor Yellow
    }
}

# --- 8. AdminSeeder -----------------------------------------------------------

Write-Host ""
Write-Host ">>> Writing AdminSeeder..." -ForegroundColor Cyan

$seeder = @'
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@medflow.local'],
            [
                'name'              => 'System Administrator',
                'email'             => 'admin@medflow.local',
                'password'          => Hash::make('Admin@MedFlow2024!'),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user ready:');
        $this->command->info('  Email:    admin@medflow.local');
        $this->command->info('  Password: Admin@MedFlow2024!');
        $this->command->warn('  Change the password after first login!');
    }
}
'@

Save-File "database\seeders\AdminSeeder.php" $seeder
Write-Host "    AdminSeeder written" -ForegroundColor Green

# --- 9. Login view ------------------------------------------------------------

Write-Host ""
Write-Host ">>> Writing login.blade.php (MedFlow design)..." -ForegroundColor Cyan

$loginView = @'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In - MedFlow CRM</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 15px; }
body {
  font-family: 'DM Sans', -apple-system, sans-serif;
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #0a1628 0%, #1a2d52 40%, #0f4c81 100%);
  position: relative;
}
body::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(circle at 30% 40%, rgba(37,99,235,0.15) 0%, transparent 60%),
    radial-gradient(circle at 70% 80%, rgba(5,150,105,0.10) 0%, transparent 50%);
  pointer-events: none;
}
.login-card {
  position: relative; width: 420px; padding: 48px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  backdrop-filter: blur(40px);
  -webkit-backdrop-filter: blur(40px);
  animation: slideUp 0.6s ease-out both;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}
.login-logo {
  font-family: 'Instrument Serif', Georgia, serif;
  font-size: 2.4rem; color: #fff;
  margin-bottom: 8px; letter-spacing: -0.5px;
}
.login-logo span { color: #2563eb; }
.login-subtitle { color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 36px; }
.alert-error {
  background: rgba(220,38,38,0.12);
  border: 1px solid rgba(220,38,38,0.3);
  border-radius: 10px; color: #fca5a5;
  font-size: 0.85rem; padding: 12px 16px; margin-bottom: 20px;
}
.login-field { margin-bottom: 20px; }
.login-field label {
  display: block; color: rgba(255,255,255,0.6);
  font-size: 0.75rem; font-weight: 600;
  margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.6px;
}
.login-field input {
  width: 100%; padding: 14px 16px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px; color: #fff;
  font-size: 0.95rem; font-family: 'DM Sans', sans-serif;
  transition: border-color 0.2s, background 0.2s; outline: none;
}
.login-field input::placeholder { color: rgba(255,255,255,0.25); }
.login-field input:focus { border-color: #2563eb; background: rgba(37,99,235,0.08); }
.login-field input.is-invalid { border-color: rgba(220,38,38,0.6); background: rgba(220,38,38,0.06); }
.field-error { color: #fca5a5; font-size: 0.78rem; margin-top: 6px; }
.remember-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.remember-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; }
.remember-row label {
  color: rgba(255,255,255,0.5); font-size: 0.85rem; cursor: pointer;
  margin: 0; text-transform: none; letter-spacing: normal; font-weight: 400;
}
.login-btn {
  width: 100%; padding: 14px; background: #2563eb; color: #fff;
  border: none; border-radius: 10px; font-size: 0.95rem; font-weight: 600;
  cursor: pointer; transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
  font-family: 'DM Sans', sans-serif; margin-top: 12px;
}
.login-btn:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 8px 25px rgba(37,99,235,0.35); }
.login-btn:active { transform: translateY(0); }
.login-footer { margin-top: 28px; text-align: center; color: rgba(255,255,255,0.3); font-size: 0.78rem; }
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">Med<span>Flow</span></div>
  <div class="login-subtitle">Clinic Management Platform - Sign in to continue</div>

  @if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('login') }}" novalidate>
    @csrf

    <div class="login-field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email"
        value="{{ old('email') }}"
        placeholder="admin@medflow.local"
        autocomplete="email"
        class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
        required autofocus>
      @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="login-field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password"
        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
        autocomplete="current-password"
        class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
        required>
      @error('password')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="remember-row">
      <input type="checkbox" id="remember" name="remember">
      <label for="remember">Keep me signed in</label>
    </div>

    <button type="submit" class="login-btn">Sign In</button>
  </form>

  <div class="login-footer">MedFlow CRM v2.0 - Powered by AI</div>
</div>
</body>
</html>
'@

Save-File "resources\views\auth\login.blade.php" $loginView
Write-Host "    login.blade.php written" -ForegroundColor Green

# --- 10. Dashboard placeholder ------------------------------------------------

Write-Host ""
Write-Host ">>> Writing dashboard placeholder..." -ForegroundColor Cyan

$dashboard = @'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - MedFlow CRM</title>
<style>
body { font-family: system-ui, sans-serif; background: #f8f9fb; color: #0f172a; margin: 0; padding: 40px; }
.card { background: #fff; border-radius: 12px; padding: 32px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
h1 { font-size: 1.5rem; margin-bottom: 8px; }
p  { color: #475569; margin-bottom: 20px; }
form button { background: #dc2626; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
</style>
</head>
<body>
<div class="card">
  <h1>Welcome, {{ Auth::user()->name }}</h1>
  <p>Role: <strong>{{ Auth::user()->role }}</strong></p>
  <p>MedFlow CRM Dashboard - full UI coming in the next phase.</p>
  <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Sign Out</button>
  </form>
</div>
</body>
</html>
'@

Save-File "resources\views\dashboard\index.blade.php" $dashboard
Write-Host "    Dashboard placeholder written" -ForegroundColor Green

# --- 11. Run artisan commands -------------------------------------------------

Write-Host ""
Write-Host ">>> Installing PHP dependencies..." -ForegroundColor Cyan
composer install --no-interaction

Write-Host ""
Write-Host ">>> Generating application key..." -ForegroundColor Cyan
php artisan key:generate --force

Write-Host ""
Write-Host ">>> Running migrations..." -ForegroundColor Cyan
php artisan migrate --force

Write-Host ""
Write-Host ">>> Seeding admin user..." -ForegroundColor Cyan
php artisan db:seed --class=AdminSeeder --force

Write-Host ""
Write-Host ">>> Clearing caches..." -ForegroundColor Cyan
php artisan optimize:clear

# --- Done ---------------------------------------------------------------------

Stop-Transcript

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Full log saved to: $logFile" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  Admin login:" -ForegroundColor Cyan
Write-Host "    Email:    admin@medflow.local"
Write-Host "    Password: Admin@MedFlow2024!"
Write-Host ""
Write-Host "  Database: SQLite (database/database.sqlite) - no server needed"
Write-Host ""
Write-Host "  Starting dev server in a new window..." -ForegroundColor Cyan
Write-Host "  Open http://localhost:8000/login in your browser"
Write-Host ""

# Launch the dev server in a new terminal window so this one stays free
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$((Get-Location).Path)'; php artisan serve"
