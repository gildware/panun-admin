#!/usr/bin/env python3
"""
Copy photorealistic painting images from Cursor assets into service-images/.
Expects: {assets}/{slug}-thumbnail.png and {slug}-cover.png
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

from PIL import Image

SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
SERVICE_IMG = ROOT / "service-images"
PROMPTS = ROOT / "data" / "painting-photo-prompts.json"


def copy_service_image(slug: str, kind: str) -> None:
    src = SRC / f"{slug}-{kind}.png"
    if not src.is_file():
        raise SystemExit(f"Missing source image: {src}")
    out_dir = SERVICE_IMG / slug
    out_dir.mkdir(parents=True, exist_ok=True)
    img = Image.open(src).convert("RGB")
    size = (1024, 1024) if kind == "thumbnail" else (1536, 1024)
    img = img.resize(size, Image.Resampling.LANCZOS)
    out = out_dir / f"{kind}.png"
    img.save(out, "PNG", optimize=True)
    print(f"Wrote {out} ({img.size[0]}x{img.size[1]})")


def main() -> None:
    rows = json.loads(PROMPTS.read_text())
    missing = []
    for row in rows:
        slug = row["slug"]
        if not (SRC / f"{slug}-thumbnail.png").is_file() or not (SRC / f"{slug}-cover.png").is_file():
            missing.append(slug)
            continue
        copy_service_image(slug, "thumbnail")
        copy_service_image(slug, "cover")
    print(f"\nPrepared {len(rows) - len(missing)}/{len(rows)} services.")
    if missing:
        print(f"Missing photorealistic sources for: {', '.join(missing)}")
        sys.exit(1)


if __name__ == "__main__":
    main()
