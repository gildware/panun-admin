#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")/../.."
ADMIN="$(pwd)"

ASSETS="/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets"
MANIFEST="$ADMIN/scripts/data/missing-catalog-manifest.json"

ready=$(python3 -c "
import json
from pathlib import Path
assets = Path('$ASSETS')
m = json.load(open('$MANIFEST'))
print(sum(1 for s in m['services'] if (assets/f\"{s['slug']}-thumbnail.png\").is_file() and (assets/f\"{s['slug']}-cover.png\").is_file()))
")
total=$(python3 -c "import json; print(len(json.load(open('$MANIFEST'))['services']))")
echo "Photorealistic images: $ready/$total"
if [ "$ready" -lt "$total" ]; then
  echo "ERROR: Not all images ready yet ($((total - ready)) missing)"
  exit 1
fi

echo "==> Preparing resized assets..."
python3 scripts/assets/prepare_missing_catalog_assets.py

echo "==> Variant icons..."
python3 scripts/assets/generate_missing_catalog_assets.py

echo "==> Importing all services to live..."
LIVE_DB_PASSWORD="${LIVE_DB_PASSWORD:?Set LIVE_DB_PASSWORD}" \
  IMPORT_REFRESH_EXISTING=1 \
  IMPORT_REQUIRE_PHOTO_SOURCE=1 \
  php artisan tinker scripts/import-missing-catalog-live.php

echo "==> Verifying live DB..."
LIVE_DB_PASSWORD="${LIVE_DB_PASSWORD}" php artisan tinker --execute="
\$manifest = json_decode(file_get_contents(base_path('scripts/data/missing-catalog-manifest.json')), true);
\$conn = 'live_verify';
config(['database.connections.'.\$conn => [
  'driver'=>'mysql','host'=>'82.25.121.201','port'=>'3306',
  'database'=>'u397782854_live_pk_dec','username'=>'u397782854_live_pk_usr',
  'password'=>env('LIVE_DB_PASSWORD'),'charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>'','strict'=>true,
]]);
\$slugs = array_column(\$manifest['services'], 'slug');
\$found = \Modules\ServiceManagement\Entities\Service::on(\$conn)->withoutGlobalScopes()->whereIn('slug', \$slugs)->count();
\$missing = count(\$slugs) - \$found;
echo \"Live: \$found/\".count(\$slugs).\" services\";
if (\$missing > 0) { echo \" — MISSING \$missing\"; exit(1); }
echo \" — OK\";
"
