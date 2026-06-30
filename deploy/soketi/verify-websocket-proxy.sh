#!/usr/bin/env bash
# Quick checks after deploying Soketi WebSocket proxy. Run from Laravel root.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PHP="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"

echo "=== .htaccess proxy rules ==="
grep -n '6001' "$ROOT/public/.htaccess" || echo "MISSING: no Soketi proxy rules in public/.htaccess"

echo ""
echo "=== websocket config (API-facing) ==="
$PHP artisan tinker --execute="print_r(config('inappcallmodule.websocket'));"

echo ""
echo "=== local soketi ==="
curl -s -o /dev/null -w "local_6001=%{http_code}\n" http://127.0.0.1:6001/

echo ""
echo "=== public /app proxy (no upgrade header) ==="
curl -s -o /dev/null -w "public_app=%{http_code}\n" --max-time 5 \
  "https://dev.panunkaergar.com/app/panun-key" || echo "public_app=blocked"

echo ""
echo "=== public websocket upgrade ==="
curl -i -N --max-time 5 \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  "https://dev.panunkaergar.com/app/panun-key" 2>&1 | head -20
