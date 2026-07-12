#!/usr/bin/env python3
"""Copy remaining category service images and recolor variant icons."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

from PIL import Image

BRAND = (26, 35, 58)
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
SERVICE_IMG = ROOT / "service-images"
VARIANT_IMG = ROOT / "variant-icons"
MANIFEST = Path(__file__).resolve().parents[1] / "data" / "remaining-services-manifest.php"


def recolor(img: Image.Image) -> Image.Image:
    img = img.convert("RGBA")
    px = img.load()
    w, h = img.size
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            if a < 20:
                px[x, y] = (255, 255, 255, 0)
                continue
            if r > 235 and g > 235 and b > 235:
                px[x, y] = (255, 255, 255, 255)
                continue
            strength = max(0, min(255, 255 - (r + g + b) // 3))
            if strength < 12:
                px[x, y] = (255, 255, 255, 255)
                continue
            blend = strength / 255
            nr = int(255 + (BRAND[0] - 255) * blend)
            ng = int(255 + (BRAND[1] - 255) * blend)
            nb = int(255 + (BRAND[2] - 255) * blend)
            px[x, y] = (nr, ng, nb, 255)
    return img


def slugs_from_manifest() -> list[str]:
    text = MANIFEST.read_text()
    return re.findall(r"\$service\('[^']+',\s*'([^']+)'", text)


def save_service(slug: str, kind: str) -> None:
    src = SRC / f"{slug}-{kind}.png"
    if not src.is_file():
        raise SystemExit(f"Missing {src}")
    out_dir = SERVICE_IMG / slug
    out_dir.mkdir(parents=True, exist_ok=True)
    img = Image.open(src).convert("RGB")
    size = (1024, 1024) if kind == "thumbnail" else (1536, 1024)
    img = img.resize(size, Image.Resampling.LANCZOS)
    out = out_dir / f"{kind}.png"
    img.save(out, "PNG", optimize=True)
    print(f"Wrote {out}")


def save_variant(src_name: str, out_name: str) -> None:
    src = SRC / src_name
    if not src.is_file():
        return
    out = VARIANT_IMG / out_name
    result = recolor(Image.open(src))
    result = result.resize((512, 512), Image.Resampling.LANCZOS)
    result.convert("RGB").save(out, "PNG", optimize=True)
    print(f"Wrote {out}")


def main() -> None:
    slugs = slugs_from_manifest() if MANIFEST.is_file() else []
    if not slugs:
        print("No manifest slugs found", file=sys.stderr)
        sys.exit(1)
    missing = []
    for slug in slugs:
        for kind in ("thumbnail", "cover"):
            src = SRC / f"{slug}-{kind}.png"
            if not src.is_file():
                missing.append(f"{slug}-{kind}.png")
                continue
            save_service(slug, kind)
    for src in sorted(SRC.glob("*-src.png")):
        out = src.name.replace("-src.png", ".png")
        save_variant(src.name, out)
    for src in sorted(SRC.glob("*-book-site-inspection-src.png")):
        out = src.name.replace("-src.png", ".png")
        save_variant(src.name, out)
    if missing:
        print("MISSING:", ", ".join(missing), file=sys.stderr)
        sys.exit(2)
    print("Done.")


if __name__ == "__main__":
    main()
