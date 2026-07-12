#!/usr/bin/env python3
"""
Copy photorealistic missing-catalog images from the Cursor assets folder into
scripts/assets/service-images/ — same pipeline as prepare_repair_assets.py.

Expects per service slug:
  {assets}/{slug}-thumbnail.png  -> service-images/{slug}/thumbnail.png (1024x1024)
  {assets}/{slug}-cover.png      -> service-images/{slug}/cover.png (1536x1024)

Variant icons are generated separately by generate_missing_catalog_assets.py.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

from PIL import Image

SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
SERVICE_IMG = ROOT / "service-images"
MANIFEST = ROOT.parent / "data" / "missing-catalog-manifest.json"


def copy_service_image(slug: str, kind: str) -> None:
    src = SRC / f"{slug}-{kind}.png"
    if not src.is_file():
        raise SystemExit(f"Missing source image: {src}")
    out_dir = SERVICE_IMG / slug
    out_dir.mkdir(parents=True, exist_ok=True)
    img = Image.open(src).convert("RGB")
    if kind == "thumbnail":
        img = img.resize((1024, 1024), Image.Resampling.LANCZOS)
    else:
        img = img.resize((1536, 1024), Image.Resampling.LANCZOS)
    out = out_dir / f"{kind}.png"
    img.save(out, "PNG", optimize=True)
    print(f"Wrote {out} ({img.size[0]}x{img.size[1]})")


def main() -> None:
    if not MANIFEST.is_file():
        print(f"Missing manifest: {MANIFEST}", file=sys.stderr)
        sys.exit(1)

    data = json.loads(MANIFEST.read_text())
    slugs = [svc["slug"] for svc in data["services"]]
    missing = []
    prepared = 0

    for slug in slugs:
        thumb_src = SRC / f"{slug}-thumbnail.png"
        cover_src = SRC / f"{slug}-cover.png"
        if not thumb_src.is_file() or not cover_src.is_file():
            missing.append(slug)
            continue
        copy_service_image(slug, "thumbnail")
        copy_service_image(slug, "cover")
        prepared += 1

    print(f"\nPrepared {prepared}/{len(slugs)} services.")
    if missing:
        print(f"Missing photorealistic sources for {len(missing)} services (generate into {SRC}).")
        if len(missing) <= 20:
            for slug in missing:
                print(f"  - {slug}")
        else:
            for slug in missing[:10]:
                print(f"  - {slug}")
            print(f"  ... and {len(missing) - 10} more")


if __name__ == "__main__":
    main()
