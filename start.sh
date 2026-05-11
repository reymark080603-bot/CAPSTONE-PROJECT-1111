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

echo "→ Preparing storage directories..."
mkdir -p storage/app/public/ebooks
mkdir -p storage/app/public/covers
mkdir -p storage/app/public/uploads/book-covers
chmod -R 775 storage/app/public

echo "→ Fixing storage symlink..."
rm -rf public/storage
php artisan storage:link --force

echo "=== Starting PHP server on port ${PORT:-8000} ==="
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
