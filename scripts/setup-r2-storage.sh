#!/usr/bin/env bash
# Configure Cloudflare R2 for Panun Kaergar admin (run on server or locally after filling .env).
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Panun Kaergar — R2 storage setup"
echo ""
echo "1. Create R2 bucket in Cloudflare dashboard"
echo "2. Create R2 API token (Object Read & Write)"
echo "3. Enable public access (r2.dev URL) OR connect custom domain"
echo "4. Fill .env AWS_* values OR save credentials in Admin → Configuration → Storage Connection"
echo "5. Set environment folder: STORAGE_PATH_PREFIX=local|dev|prod (or Admin → Environment folder)"
echo ""

if ! grep -q '^AWS_ENDPOINT=.' .env 2>/dev/null; then
  echo "WARN: AWS_ENDPOINT is empty in .env — add R2 credentials before verify."
fi

php artisan config:clear
php artisan storage:verify-r2

echo ""
echo "If verify passed:"
echo "  php artisan storage:sync-to-r2 --dry-run"
echo "  php artisan storage:sync-to-r2"
echo "  php artisan assets:sync-to-r2 --dry-run"
echo "  php artisan assets:sync-to-r2"
echo "  Then set STATIC_ASSET_URL=https://<r2-public>/<prefix> in .env"
echo "  Then in admin: Storage Connection → 3rd Party Storage"
echo "  See docs/performance/static-assets-cdn.md"
