#!/usr/bin/env bash
# Restore Laravel Passport OAuth keys on a deployed server.
# Run from the Laravel project root (where artisan lives).
#
# Usage (SSH into dev/prod, then):
#   cd /home/u397782854/domains/panunkaergar.com/public_html/subdomains/dev
#   bash scripts/fix-passport-keys.sh
#
# Symptom fixed: API returns 500 with "Invalid key supplied" (CryptKey.php).

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ ! -f artisan ]]; then
  echo "Error: artisan not found. Run this script from the Laravel project root."
  exit 1
fi

echo "==> Project: $ROOT"

# Empty PASSPORT_* env vars override file keys and cause "Invalid key supplied".
if [[ -f .env ]]; then
  if grep -qE '^PASSPORT_(PRIVATE|PUBLIC)_KEY=' .env; then
    echo "==> WARNING: .env defines PASSPORT_PRIVATE_KEY or PASSPORT_PUBLIC_KEY."
    echo "    If those values are empty or truncated, auth will fail."
    echo "    Comment them out unless you intentionally store keys in .env."
  fi
fi

echo "==> Generating oauth-public.key and oauth-private.key in storage/"
php artisan passport:keys --force

chmod 600 storage/oauth-private.key 2>/dev/null || true
chmod 644 storage/oauth-public.key 2>/dev/null || true

echo "==> Clearing config/cache"
php artisan config:clear
php artisan cache:clear

echo "==> Verifying keys load (expect Unauthenticated, NOT Invalid key supplied)"
HTTP_CODE="$(curl -s -o /tmp/passport-check.json -w '%{http_code}' \
  -X GET "${APP_URL:-https://dev.panunkaergar.com}/api/v1/provider/account/overview" \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer invalid' \
  -H 'X-localization: en' || true)"

if [[ -f /tmp/passport-check.json ]]; then
  MSG="$(python3 -c "import json; print(json.load(open('/tmp/passport-check.json')).get('message',''))" 2>/dev/null || true)"
  echo "    HTTP $HTTP_CODE — message: ${MSG:-<no json>}"
  if [[ "$MSG" == "Invalid key supplied" ]]; then
    echo "==> FAILED: Keys still invalid. Check .env PASSPORT_* vars or file permissions."
    exit 1
  fi
  if [[ "$MSG" == "Unauthenticated." ]] || [[ "$HTTP_CODE" == "401" ]]; then
    echo "==> SUCCESS: Passport keys are working."
  else
    echo "==> Keys generated. Re-test an authenticated endpoint after logging in from the app."
  fi
else
  echo "==> Keys generated. Could not auto-verify (curl failed). Test manually."
fi

echo ""
echo "Next steps:"
echo "  1. Log out and log back in on Provider/User apps (old tokens are invalid)."
echo "  2. After future update.zip deploys, re-run this script if auth breaks again."
