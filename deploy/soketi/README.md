# Soketi (WebSocket signaling for in-app calls)

Soketi is a Pusher-compatible server used for real-time call status and WebRTC signaling. FCM push is still used when the app is in the background.

## 1. Laravel `.env` (same server as the API)

```env
BROADCAST_DRIVER=pusher
IN_APP_CALL_WEBSOCKET_ENABLED=true

PUSHER_APP_ID=panun-app
PUSHER_APP_KEY=panun-key
PUSHER_APP_SECRET=REPLACE_WITH_LONG_RANDOM_SECRET
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# Mobile apps (API config) — public domain on 443 via reverse proxy; PHP keeps 127.0.0.1 above.
PUSHER_PUBLIC_HOST=dev.panunkaergar.com
PUSHER_PUBLIC_PORT=443
PUSHER_PUBLIC_SCHEME=https
```

Use the **same** `PUSHER_APP_*` values in `deploy/soketi/soketi.env`.

After editing `.env`:

```bash
cd /path/to/panun-admin
composer update pusher/pusher-php-server
php artisan config:clear
```

## 2. Deploy this folder to the server

From your machine (after git push), on the server you should see:

```text
deploy/
  coturn/
  supervisor/
  soketi/          ← new
```

If `soketi/` is missing, pull latest code or copy these files manually.

## 3. Shared hosting (no Docker) — recommended for Hostinger-style VPS

```bash
cd /path/to/deploy/soketi
cp soketi.env.example soketi.env
nano soketi.env          # set PUSHER_APP_* secrets (match Laravel .env)
chmod +x start-soketi.sh

# Test once in foreground
./start-soketi.sh
```

Requires **Node.js 18+**. Check with `node -v`.

### Run with Supervisor

```bash
cp ../supervisor/soketi.conf.example /path/to/supervisor/conf.d/soketi.conf
# Edit paths and USER in that file, then:
supervisorctl reread
supervisorctl update
supervisorctl start panun-soketi
```

## 4. Docker (only if Docker is available)

```bash
cd deploy/soketi
docker compose up -d
```

## 5. Mobile apps

Apps read WebSocket settings from `GET /api/v1/.../in-app-call/config`.

- **Laravel → Soketi (PHP broadcast):** keep `PUSHER_HOST=127.0.0.1`, `PUSHER_PORT=6001`, `PUSHER_SCHEME=http`.
- **Phones on the internet:** set `PUSHER_PUBLIC_HOST`, `PUSHER_PUBLIC_PORT`, `PUSHER_PUBLIC_SCHEME` so the config API returns your public domain (not `127.0.0.1`). Port 6001 is usually blocked on shared hosting; proxy WebSocket on **443** instead.

### Apache / LiteSpeed `.htaccess` proxy

Insert the rules from `htaccess-websocket-snippet.txt` into `public/.htaccess` **before** the `RewriteRule ^ index.php` line.

Then in `.env`:

```env
PUSHER_PUBLIC_HOST=dev.panunkaergar.com
PUSHER_PUBLIC_PORT=443
PUSHER_PUBLIC_SCHEME=https
```

```bash
php artisan config:clear
```

Verify (expect `101 Switching Protocols` if proxy works, or `500` if mod_proxy is disabled):

```bash
curl -i -N --max-time 5 \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  https://dev.panunkaergar.com/app/panun-key
```

If `.htaccess` proxy returns 500, shared hosting likely blocks `mod_proxy` — contact Hostinger support or use HTTP polling fallback until VPS.

Example with Nginx (VPS):

- Proxy `/app` to `127.0.0.1:6001`
- Set `PUSHER_PUBLIC_HOST`, `PUSHER_PUBLIC_PORT=443`, `PUSHER_PUBLIC_SCHEME=https`

## 6. Verify

```bash
curl -s http://127.0.0.1:6001/
# or check supervisor log
tail -f deploy/soketi/soketi.log
```

Place a test call with both apps in the foreground. Soketi logs should show channel subscribe/auth events; `/signals` polling should drop sharply.

## Channels

| Channel | Purpose |
|---------|---------|
| `private-in-app-call.user.{userId}` | Ring + call status |
| `private-in-app-call.{callId}` | WebRTC offer / answer / ICE |

Auth: `POST /broadcasting/auth` with the same Bearer token as the REST API.
