#!/bin/bash
# Prepare gardening catalog assets from prompt-generated images.
set -euo pipefail

ROOT="/Users/kamran/Desktop/panun kaergar/panun-admin"
SRC="/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets"
CAT="$ROOT/scripts/assets/category-icons"
VAR="$ROOT/scripts/assets/variant-icons"
SVC="$ROOT/scripts/assets/service-images"

copy_cat() {
  cp "$SRC/$1.png" "$CAT/$1.png"
}

copy_cat gardening
copy_cat lawn-grass-care
copy_cat planting-soil-care
copy_cat pruning-trimming
copy_cat garden-cleanup-maintenance

copy_variant() {
  local slug="$1" key="$2" base="$3"
  cp "$SRC/variant-${base}.png" "$VAR/${slug}-${key}.png"
}

# Inspection variants
for slug in grass-edging-levelling planting-repotting soil-preparation-fertilizing drip-irrigation-setup hedge-cutting tree-shrub-pruning plant-shaping-deadheading seasonal-garden-maintenance plant-pest-disease-treatment; do
  copy_variant "$slug" book-site-inspection book-site-inspection
done

# Size variants
for slug in lawn-mowing-trimming garden-cleanup-weeding monthly-garden-maintenance-plan; do
  copy_variant "$slug" small small
  copy_variant "$slug" medium medium
  copy_variant "$slug" large large
done

copy_variant terrace-balcony-garden-setup small small
copy_variant terrace-balcony-garden-setup large large
copy_variant leaf-debris-removal small small
copy_variant leaf-debris-removal large large

copy_variant book-a-gardener hourly hourly
copy_variant book-a-gardener half-day half-day
copy_variant book-a-gardener full-day full-day

for slug in lawn-mowing-trimming grass-edging-levelling planting-repotting soil-preparation-fertilizing terrace-balcony-garden-setup drip-irrigation-setup hedge-cutting tree-shrub-pruning plant-shaping-deadheading garden-cleanup-weeding leaf-debris-removal seasonal-garden-maintenance monthly-garden-maintenance-plan plant-pest-disease-treatment book-a-gardener; do
  mkdir -p "$SVC/$slug"
  cp "$SRC/${slug}-thumbnail.png" "$SVC/$slug/thumbnail.png"
  cp "$SRC/${slug}-thumbnail.png" "$SVC/$slug/cover.png"
done

echo "Gardening assets prepared."
