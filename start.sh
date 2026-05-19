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

echo "→ Preparing storage (ABSOLUTE)..."
mkdir -p /app/storage/app/public/ebooks
mkdir -p /app/storage/app/public/covers
mkdir -p /app/storage/app/public/uploads/book-covers
chmod -R 777 /app/storage/app/public

echo "→ Fixing storage symlink (ABSOLUTE)..."
rm -rf /app/public/storage
ln -sf /app/storage/app/public /app/public/storage

echo "=== Starting PHP server on port ${PORT:-8000} ==="
php -d upload_max_filesize=150M \
    -d post_max_size=200M \
    -d memory_limit=512M \
    -d max_execution_time=300 \
    -d max_input_time=300 \
    artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
