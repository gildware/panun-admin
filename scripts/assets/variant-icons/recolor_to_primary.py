#!/usr/bin/env python3
"""Recolor reference-style icons to project primary (#25274D) at full resolution."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

PRIMARY = (37, 39, 77)  # #25274D
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
OUT = Path(__file__).resolve().parent / "samples"

SOURCES = {
    "50-primary-v1-classic": "40-ref-v1-classic.png",
    "51-primary-v2-big-lens": "41-ref-v2-big-lens.png",
    "52-primary-v3-wide-door": "42-ref-v3-wide-door.png",
    "53-primary-v4-big-clipboard": "43-ref-v4-big-clipboard.png",
    "54-primary-v5-compact": "44-ref-v5-compact.png",
}


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
            # Keep white / near-white background
            if r > 235 and g > 235 and b > 235:
                px[x, y] = (255, 255, 255, 255)
                continue
            # Map all icon strokes/fills to brand primary, preserve anti-alias alpha
            strength = max(0, min(255, 255 - (r + g + b) // 3))
            if strength < 12:
                px[x, y] = (255, 255, 255, 255)
                continue
            blend = strength / 255
            nr = int(255 + (PRIMARY[0] - 255) * blend)
            ng = int(255 + (PRIMARY[1] - 255) * blend)
            nb = int(255 + (PRIMARY[2] - 255) * blend)
            px[x, y] = (nr, ng, nb, 255)
    return img


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    for out_name, src_name in SOURCES.items():
        src = ASSETS / src_name
        if not src.is_file():
            raise SystemExit(f"Missing source: {src}")
        img = Image.open(src)
        print(f"Source {src_name}: {img.size}")
        result = recolor(img)
        path = OUT / f"{out_name}.png"
        result.convert("RGB").save(path, "PNG", optimize=True)
        print(f"Wrote {path} ({result.size[0]}x{result.size[1]})")


if __name__ == "__main__":
    main()
