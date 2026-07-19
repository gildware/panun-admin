# Static assets: Cloudflare cache + R2

Goal: stop Hostinger from serving `/public/assets/*` CSS/JS under load.

## A) Cloudflare cache rule (do first — ~10 minutes)

Works even before R2. Caches assets at the edge when the browser still requests Hostinger URLs.

1. Open [Cloudflare Dashboard](https://dash.cloudflare.com) → zone **panunkaergar.com**
2. **Caching** → **Cache Rules** → **Create rule**
3. Rule name: `Cache public assets`
4. When incoming requests match:
   - Field: **URI Path**
   - Operator: **starts with**
   - Value: `/public/assets/`
5. Then:
   - **Cache eligibility**: Eligible for cache
   - **Edge TTL**: Override → 1 month (or 1 year)
   - **Browser TTL**: Override → 1 week
6. Deploy

Optional second rule (if some assets are requested without `/public`):

- URI Path starts with `/assets/`
- Same cache settings

After deploy, hard-refresh admin and confirm response headers on a CSS/JS file:

- `cf-cache-status: HIT` (or `MISS` then `HIT` on second load)
- Not `DYNAMIC`

Purge only when you change CSS/JS:

- Caching → Configuration → **Purge Everything** (or custom purge of `/public/assets/*`)

## B) Move CSS/JS to R2 (permanent fix)

Uploads `public/assets` (~50MB) into the same R2 bucket used for category images.

### 1. Deploy this code to live

Includes:

- `php artisan assets:sync-to-r2`
- `STATIC_ASSET_URL` support (only rewrites `assets/*`, not `/storage`)

### 2. On the live server (SSH or Hostinger terminal)

```bash
cd /path/to/panun-admin   # project root on Hostinger

php artisan assets:sync-to-r2 --dry-run
php artisan assets:sync-to-r2
# Optional faster first pass:
# php artisan assets:sync-to-r2 --only=admin-module,landing,common,provider-module,libs
```

R2 credentials come from **Admin → Storage Connection** (already working for images).

### 3. Set env on live

Use the URL printed by the command. For your current public bucket it will look like:

```env
STATIC_ASSET_URL=https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod
```

Do **not** set a bare `ASSET_URL` that would rewrite `storage/` media unless you know it is safe. Prefer `STATIC_ASSET_URL`.

Then:

```bash
php artisan config:clear
php artisan view:clear
```

### 4. Verify

1. Open admin login → View Source  
   CSS/JS should start with `https://pub-….r2.dev/prod/assets/…`
2. Open one JS URL in a new tab → should download quickly (not hang)
3. Cold load admin dashboard → should be seconds, not ~60s

### 5. When you change CSS/JS later

```bash
php artisan assets:sync-to-r2 --force --only=admin-module
# bump ?v= already handled by $adminAssetVersion in layouts
```

## Order of operations

1. Cloudflare cache rule today  
2. Deploy code + `assets:sync-to-r2` + `STATIC_ASSET_URL`  
3. Keep Cloudflare rule as backup for any leftover Hostinger asset URLs

## Rollback

Remove or blank `STATIC_ASSET_URL` in `.env`, then:

```bash
php artisan config:clear
php artisan view:clear
```

Assets fall back to Hostinger `/public/assets/…` immediately.
