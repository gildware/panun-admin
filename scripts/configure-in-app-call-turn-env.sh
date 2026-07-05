#!/usr/bin/env bash
# Configure Laravel .env for an external Coturn server (no root/sudo required).
#
# Use when Coturn runs on a separate VPS. Do NOT run setup-in-app-call-turn.sh
# on shared hosting — that script installs Coturn and needs root.
#
# Usage (from Laravel root, e.g. public_html/subdomains/dev):
#   bash scripts/configure-in-app-call-turn-env.sh \
#     turn:YOUR_TURN_SERVER_IP:3478 panun_turn YOUR_TURN_SECRET
#
# Or with env vars:
#   TURN_URL=turn:1.2.3.4:3478 TURN_USERNAME=panun_turn TURN_CREDENTIAL=secret \
#     bash scripts/configure-in-app-call-turn-env.sh

set -euo pipefail

LARAVEL_ROOT="${LARAVEL_ROOT:-}"
if [[ -z "$LARAVEL_ROOT" ]]; then
  for p in \
    "/home/u397782854/domains/panunkaergar.com/public_html/subdomains/dev" \
    "/home/u397782854/domains/panunkaergar.com/dev" \
    "$(pwd)"; do
    if [[ -f "$p/artisan" ]]; then
      LARAVEL_ROOT="$p"
      break
    fi
  done
fi

if [[ -z "$LARAVEL_ROOT" ]] || [[ ! -f "$LARAVEL_ROOT/artisan" ]]; then
  echo "Error: Laravel root not found. cd to your Laravel folder or set LARAVEL_ROOT."
  exit 1
fi

TURN_URL="${1:-${TURN_URL:-}}"
TURN_USERNAME="${2:-${TURN_USERNAME:-}}"
TURN_CREDENTIAL="${3:-${TURN_CREDENTIAL:-}}"
TURN_TLS_URL="${TURN_TLS_URL:-}"

if [[ -z "$TURN_URL" || -z "$TURN_USERNAME" || -z "$TURN_CREDENTIAL" ]]; then
  echo "Usage: bash $0 turn:HOST:3478 USERNAME CREDENTIAL"
  echo "   or: TURN_URL=... TURN_USERNAME=... TURN_CREDENTIAL=... bash $0"
  exit 1
fi

ENV_FILE="$LARAVEL_ROOT/.env"
touch "$ENV_FILE"

set_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    echo "${key}=${val}" >>"$ENV_FILE"
  fi
}

set_env "IN_APP_CALL_ENABLED" "true"
set_env "STUN_URL" "stun:stun.l.google.com:19302"
set_env "TURN_URL" "$TURN_URL"
set_env "TURN_USERNAME" "$TURN_USERNAME"
set_env "TURN_CREDENTIAL" "$TURN_CREDENTIAL"
if [[ -n "$TURN_TLS_URL" ]]; then
  set_env "TURN_TLS_URL" "$TURN_TLS_URL"
fi

cd "$LARAVEL_ROOT"

PHP_BIN="$(command -v php || true)"
if [[ -z "$PHP_BIN" ]] && [[ -x /opt/alt/php83/usr/bin/php ]]; then
  PHP_BIN="/opt/alt/php83/usr/bin/php"
fi
if [[ -n "$PHP_BIN" ]]; then
  echo "==> Clearing config cache..."
  "$PHP_BIN" artisan config:clear
  "$PHP_BIN" artisan cache:clear 2>/dev/null || true
else
  echo "==> Warning: php not found. Run: php artisan config:clear"
fi

echo ""
echo "=============================================="
echo " Laravel TURN env configured (external server)"
echo "=============================================="
echo " TURN_URL=${TURN_URL}"
echo " TURN_USERNAME=${TURN_USERNAME}"
echo " TURN_CREDENTIAL=(saved in .env)"
echo ""
echo " Verify after app login:"
echo "   GET /api/v1/customer/in-app-call/config"
echo "   should include TURN in ice_servers"
echo "=============================================="
