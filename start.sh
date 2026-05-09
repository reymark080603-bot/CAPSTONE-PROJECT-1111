#!/bin/bash
set -e

echo "=== Starting Knowly Application ==="

echo "→ Running database migrations..."
php artisan migrate --force

echo "→ Running database seeders..."
php artisan db:seed --force

echo "→ Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

echo "→ Clearing config & cache..."
php artisan config:clear
php artisan cache:clear

echo "=== Starting PHP server on port ${PORT:-8000} ==="
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
