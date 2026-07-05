#!/usr/bin/env bash
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${DIR}/soketi.env"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Missing ${ENV_FILE}. Copy soketi.env.example to soketi.env and edit secrets."
  exit 1
fi

# shellcheck disable=SC1090
source "${ENV_FILE}"

export SOKETI_DEFAULT_APP_ID="${PUSHER_APP_ID}"
export SOKETI_DEFAULT_APP_KEY="${PUSHER_APP_KEY}"
export SOKETI_DEFAULT_APP_SECRET="${PUSHER_APP_SECRET}"
export SOKETI_DEFAULT_APP_ENABLE_CLIENT_MESSAGES="false"
export SOKETI_DEFAULT_APP_ENABLED="true"
export SOKETI_DEFAULT_APP_MAX_CONNECTIONS="10000"
export SOKETI_DEFAULT_APP_MAX_BACKEND_EVENTS_PER_SEC="1000"
export SOKETI_DEBUG="${SOKETI_DEBUG:-0}"

PORT="${SOKETI_PORT:-6001}"

if ! command -v node >/dev/null 2>&1; then
  echo "Node.js is required. Install Node 18+ or use the Docker compose file instead."
  exit 1
fi

exec npx --yes @soketi/soketi start --host=127.0.0.1 --port="${PORT}"
