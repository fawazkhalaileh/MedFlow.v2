.PHONY: serve migrate fresh seed tinker routes clear logs npm-dev npm-build

# --- Dev server ---
serve:
	php artisan serve

# --- Database ---
migrate:
	php artisan migrate

fresh:
	php artisan migrate:fresh --seed

seed:
	php artisan db:seed --class=AdminSeeder

# --- Laravel ---
tinker:
	php artisan tinker

routes:
	php artisan route:list

clear:
	php artisan optimize:clear

# --- Logs ---
logs:
	tail -f storage/logs/laravel.log

# --- Node / Vite ---
npm-dev:
	npm run dev

npm-build:
	npm run build
