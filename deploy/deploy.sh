#!/usr/bin/env bash
# Skrip deploy baku untuk VPS. Jalankan dari server: bash deploy/deploy.sh
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan down || true
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
