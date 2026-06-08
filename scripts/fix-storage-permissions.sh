#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

APP_USER="${1:-www-data}"
APP_GROUP="${2:-www-data}"

echo "[1/4] Ajustando ownership de storage e bootstrap/cache para ${APP_USER}:${APP_GROUP}"
sudo chown -R "${APP_USER}:${APP_GROUP}" "${ROOT_DIR}/storage" "${ROOT_DIR}/bootstrap/cache"

echo "[2/4] Ajustando permissao de diretorios"
sudo find "${ROOT_DIR}/storage" "${ROOT_DIR}/bootstrap/cache" -type d -exec chmod 775 {} \;

echo "[3/4] Ajustando permissao de arquivos"
sudo find "${ROOT_DIR}/storage" "${ROOT_DIR}/bootstrap/cache" -type f -exec chmod 664 {} \;

echo "[4/4] Garantindo diretorio de logs"
sudo mkdir -p "${ROOT_DIR}/storage/logs"
sudo chmod 775 "${ROOT_DIR}/storage/logs"

echo "Permissoes ajustadas com sucesso."
