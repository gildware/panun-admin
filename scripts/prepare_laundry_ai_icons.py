#!/usr/bin/env python3
"""Resize and brand-recolor AI-generated laundry icons (post-process only, no drawing)."""

from __future__ import annotations

import json
import shutil
import sys
from pathlib import Path

from PIL import Image

BRAND = (26, 35, 58)
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
CAT_SRC = ROOT / "assets" / "category-icons"
VARIANT_OUT = ROOT / "assets" / "variant-icons"
MANIFEST = ROOT / "data" / "laundry-catalog-manifest.json"


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


def save_variant(name: str) -> None:
    src = SRC / f"{name}.png"
    if not src.is_file():
        raise SystemExit(f"Missing AI icon: {src}")
    out = VARIANT_OUT / f"{name}.png"
    img = recolor(Image.open(src))
    img = img.resize((512, 512), Image.Resampling.LANCZOS)
    out.parent.mkdir(parents=True, exist_ok=True)
    img.convert("RGB").save(out, "PNG", optimize=True)
    print(f"Wrote {out}")


def save_subcategory(slug: str) -> None:
    src = SRC / f"{slug}.png"
    if not src.is_file():
        raise SystemExit(f"Missing AI icon: {src}")
    dest = CAT_SRC / f"{slug}.png"
    dest.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(src, dest)
    print(f"Copied {dest}")


def main() -> None:
    for slug in ("dry-clean", "wash-laundry"):
        save_subcategory(slug)

    data = json.loads(MANIFEST.read_text())
    names: list[str] = []
    for svc in data["services"]:
        slug = svc["slug"]
        for variant in svc["variants"]:
            names.append(f"{slug}-{variant['variant_key']}")
    names.append("lehenga-dry-clean")

    missing = []
    for name in names:
        if not (SRC / f"{name}.png").is_file():
            missing.append(name)
            continue
        save_variant(name)

    if missing:
        print("MISSING:", ", ".join(missing), file=sys.stderr)
        sys.exit(2)

    print("Done. Run make_theme_pairs for subcategory light/dark next.")


if __name__ == "__main__":
    main()
