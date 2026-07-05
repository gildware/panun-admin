#!/usr/bin/env bash

NODE_BIN="$HOME/.nvm/versions/node/v18.20.8/bin"
export PATH="$NODE_BIN:${PATH:-}"

HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:6001/ 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "404" ]; then
  exit 0
fi

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"
nohup env PATH="$NODE_BIN:$PATH" ./start-soketi.sh >> soketi.log 2>&1 &
exit 0
