#!/usr/bin/env bash
# Deploy in-app call service health tab to dev (or any Hostinger Laravel root).
#
# Prereq: SSH key authorized on Hostinger (hPanel → SSH → add public key from ~/.ssh/panun_deploy.pub)
#
# Usage:
#   bash scripts/deploy-in-app-call-health.sh
#   bash scripts/deploy-in-app-call-health.sh panun-dev
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SSH_HOST="${1:-panun-dev}"
REMOTE_ROOT="/home/u397782854/domains/panunkaergar.com/dev"
PHP_BIN="/opt/alt/php83/usr/bin/php"

FILES=(
  "Modules/InAppCallModule/Http/Controllers/Web/Admin/InAppCallMonitorController.php"
  "Modules/InAppCallModule/Resources/views/admin/index.blade.php"
  "Modules/InAppCallModule/Resources/views/admin/partials/_service_status.blade.php"
  "Modules/InAppCallModule/Routes/web.php"
  "Modules/InAppCallModule/Services/InAppCallHealthService.php"
  "resources/lang/en/lang.php"
)

echo "==> Syncing ${#FILES[@]} files to ${SSH_HOST}:${REMOTE_ROOT}"
for f in "${FILES[@]}"; do
  rsync -avz "${ROOT}/${f}" "${SSH_HOST}:${REMOTE_ROOT}/${f}"
done

echo "==> Clearing Laravel caches on server"
ssh "${SSH_HOST}" "cd '${REMOTE_ROOT}' && ${PHP_BIN} artisan route:clear && ${PHP_BIN} artisan view:clear && ${PHP_BIN} artisan config:clear"

echo "==> Done. Open: https://dev.panunkaergar.com/admin/in-app-calls?tab=status"
