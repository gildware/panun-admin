#!/usr/bin/env python3
"""Recolor variant icons to #1A233A and copy repair assets into script folders."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

BRAND = (26, 35, 58)  # #1A233A
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
SERVICE_IMG = ROOT / "service-images"
VARIANT_IMG = ROOT / "variant-icons"

SLUGS = [
    "door-repair",
    "furniture-repair",
    "window-repair",
    "wardrobe-repair",
    "kitchen-cabinet-repair",
    "wooden-panel-repair",
    "roof-repair",
]


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


def copy_variant_icon(slug: str) -> None:
    src = SRC / f"{slug}-book-site-inspection-src.png"
    if not src.is_file():
        raise SystemExit(f"Missing variant source: {src}")
    out = VARIANT_IMG / f"{slug}-book-site-inspection.png"
    result = recolor(Image.open(src))
    result = result.resize((512, 512), Image.Resampling.LANCZOS)
    result.convert("RGB").save(out, "PNG", optimize=True)
    print(f"Wrote {out}")


def main() -> None:
    for slug in SLUGS:
        copy_service_image(slug, "thumbnail")
        copy_service_image(slug, "cover")
        copy_variant_icon(slug)
    print("Done preparing repair assets.")


if __name__ == "__main__":
    main()
