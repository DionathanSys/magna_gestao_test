#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$ROOT_DIR"

echo "[1/6] Atualizando autoload e dependencias PHP"
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "[2/6] Aplicando migrations"
php artisan migrate --force

echo "[3/6] Limpando caches do Laravel"
php artisan optimize:clear

echo "[4/6] Publicando/atualizando assets do Filament"
php artisan filament:upgrade

echo "[5/6] Reiniciando workers da fila"
php artisan queue:restart || true

echo "[6/6] Script finalizado com sucesso"
