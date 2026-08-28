#!/usr/bin/env python3
"""Copy photorealistic plumbing service images from Cursor assets folder."""

from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

from PIL import Image

SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
SERVICE_IMG = ROOT / "service-images"
CATALOG = ROOT.parent / "data" / "plumbing-catalog.php"


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


def load_slugs() -> list[str]:
    result = subprocess.run(
        ["php", "-r", f'$c=require "{CATALOG}"; echo json_encode(array_column($c["services"],"slug"));'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def main() -> None:
    if not CATALOG.is_file():
        print(f"Missing catalog: {CATALOG}", file=sys.stderr)
        sys.exit(1)

    slugs = [slug for slug in load_slugs() if not slug.startswith("booster-pump-")]
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

    print(f"\nPrepared {prepared}/{len(slugs)} plumbing services.")
    if missing:
        print(f"Missing photorealistic sources for {len(missing)} services:")
        for slug in missing:
            print(f"  - {slug}")
        sys.exit(2)


if __name__ == "__main__":
    main()
