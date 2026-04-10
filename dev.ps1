# =============================================================================
# MedFlow CRM - Dev Shortcuts (PowerShell)
# Usage: .\dev.ps1 <command>
# Example: .\dev.ps1 serve
# =============================================================================

param([string]$Command = "help", [string]$Extra = "")

function Show-Help {
    Write-Host @"

MedFlow CRM — Dev Commands
Usage: .\dev.ps1 <command>

  serve        Start the dev server at http://localhost:8000
  migrate      Run pending migrations
  fresh        Wipe DB and re-run all migrations + seed admin
  seed         Run AdminSeeder only
  tinker       Open Laravel REPL
  routes       List all registered routes
  clear        Clear all caches
  logs         Tail the Laravel log file
  npm-dev      Start Vite dev server (hot reload)
  npm-build    Build frontend assets for production

"@ -ForegroundColor Cyan
}

switch ($Command) {
    "serve"     { php artisan serve }
    "migrate"   { php artisan migrate }
    "fresh"     { php artisan migrate:fresh --seed }
    "seed"      { php artisan db:seed --class=AdminSeeder }
    "tinker"    { php artisan tinker }
    "routes"    { php artisan route:list }
    "clear"     { php artisan optimize:clear }
    "logs"      { Get-Content storage\logs\laravel.log -Wait -Tail 50 }
    "npm-dev"   { npm run dev }
    "npm-build" { npm run build }
    default     { Show-Help }
}
