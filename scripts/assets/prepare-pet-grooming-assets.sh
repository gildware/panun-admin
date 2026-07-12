#!/bin/bash
# Prepare pet grooming assets from Cursor assets folder (no Python generation).
# Resizes service photos and copies icons to scripts/assets paths.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SRC="/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets"
CAT_ICONS="$ROOT/scripts/assets/category-icons"
SERVICE_IMG="$ROOT/scripts/assets/service-images"
VARIANT_ICONS="$ROOT/scripts/assets/variant-icons"
LIGHT_DIR="$ROOT/scripts/category-icons/light"
DARK_DIR="$ROOT/scripts/category-icons/dark"
THEME_SCRIPT="$ROOT/scripts/assets/category-icons/make_theme_pairs.py"

CATALOG="$ROOT/scripts/data/pet-grooming-catalog.php"

mkdir -p "$CAT_ICONS" "$SERVICE_IMG" "$VARIANT_ICONS" "$LIGHT_DIR" "$DARK_DIR"

echo "==> Category icons"
for slug in pet-grooming dog-grooming cat-grooming; do
  src="$SRC/${slug}.png"
  if [[ ! -f "$src" ]]; then
    echo "MISSING category icon: $src" >&2
    exit 1
  fi
  sips -z 512 512 "$src" --out "$CAT_ICONS/${slug}.png" >/dev/null
  echo "  $slug"
done

echo "==> Service images"
SLUGS=$(php -r '$c=require "'"$CATALOG"'"; echo implode(" ", array_column($c["services"],"slug"));')
for slug in $SLUGS; do
  for kind in thumbnail cover; do
    src="$SRC/${slug}-${kind}.png"
    if [[ ! -f "$src" ]]; then
      echo "MISSING service image: $src" >&2
      exit 1
    fi
    out_dir="$SERVICE_IMG/$slug"
    mkdir -p "$out_dir"
    if [[ "$kind" == "thumbnail" ]]; then
      sips -z 1024 1024 "$src" --out "$out_dir/thumbnail.png" >/dev/null
    else
      sips -z 1024 1536 "$src" --out "$out_dir/cover.png" >/dev/null
    fi
  done
  echo "  $slug"
done

echo "==> Variant icons"
VARIANTS_JSON=$(php -r '
$c=require "'"$CATALOG"'";
$rows=[];
foreach($c["services"] as $s){foreach($s["variants"] as $v){$rows[]=$s["slug"]."-".$v["variant_key"];}}
echo implode(" ", $rows);
')
for file in $VARIANTS_JSON; do
  src="$SRC/${file}.png"
  if [[ ! -f "$src" ]]; then
    echo "MISSING variant icon: $src" >&2
    exit 1
  fi
  sips -z 512 512 "$src" --out "$VARIANT_ICONS/${file}.png" >/dev/null
done
echo "  $(echo $VARIANTS_JSON | wc -w | tr -d ' ') variant icons"

echo "==> Light/dark theme pairs"
python3 "$THEME_SCRIPT" pet-grooming dog-grooming cat-grooming

echo "All pet grooming assets ready."
