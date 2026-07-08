#!/usr/bin/env python3
"""Build light/dark PNG pairs from filled navy reference icons."""

from __future__ import annotations

import sys
from pathlib import Path

from PIL import Image

LIGHT = (0x1A, 0x23, 0x3A, 255)
DARK = (0xFF, 0xFF, 0xFF, 255)
WHITE_THRESHOLD = 245


def make_pair(source: Path, light_out: Path, dark_out: Path) -> None:
    img = Image.open(source).convert("RGBA")
    light = Image.new("RGBA", img.size, (0, 0, 0, 0))
    dark = Image.new("RGBA", img.size, (0, 0, 0, 0))

    src_px = img.load()
    light_px = light.load()
    dark_px = dark.load()

    for y in range(img.height):
        for x in range(img.width):
            r, g, b, a = src_px[x, y]
            if a < 20 or (r >= WHITE_THRESHOLD and g >= WHITE_THRESHOLD and b >= WHITE_THRESHOLD):
                continue
            light_px[x, y] = LIGHT
            dark_px[x, y] = DARK

    light_out.parent.mkdir(parents=True, exist_ok=True)
    dark_out.parent.mkdir(parents=True, exist_ok=True)
    light.save(light_out, "PNG", optimize=True)
    dark.save(dark_out, "PNG", optimize=True)
    print(f"  {source.name} -> {light_out.name}, {dark_out.name}")


def main() -> None:
    root = Path(__file__).resolve().parent
    light_dir = root.parent.parent / "category-icons" / "light"
    dark_dir = root.parent.parent / "category-icons" / "dark"
    slugs = sys.argv[1:] or ["carpentary", "carpentry-installation", "carpentry-repairs"]

    for slug in slugs:
        source = root / f"{slug}.png"
        if not source.is_file():
            raise SystemExit(f"Missing source icon: {source}")
        make_pair(source, light_dir / f"{slug}.png", dark_dir / f"{slug}.png")


if __name__ == "__main__":
    main()
