#!/bin/bash
set -e

echo "=== Starting Knowly Application ==="

echo "→ Clearing config cache (ensures fresh env vars)..."
php artisan config:clear
php artisan cache:clear

echo "→ Running database migrations..."
php artisan migrate --force

echo "→ Setting up admin/librarian account..."
php setup_admin.php

echo "→ Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

echo "=== Starting PHP server on port ${PORT:-8000} ==="
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
