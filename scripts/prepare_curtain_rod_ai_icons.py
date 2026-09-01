#!/usr/bin/env python3
"""Post-process prompt-generated curtain rod variant icons (recolor/resize only)."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

BRAND = (26, 35, 58)
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
VARIANT_OUT = ROOT / "assets" / "variant-icons"
FILES = [
    "curtain-rod-installation-standard-rod.png",
    "curtain-rod-installation-double-rod.png",
    "curtain-rod-installation-curtain-track.png",
    "curtain-rod-installation-rod-uninstall.png",
    "curtain-rod-installation-uninstall-install.png",
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


def save_variant(filename: str) -> None:
    src = SRC / filename
    if not src.is_file():
        raise SystemExit(f"Missing AI variant icon: {src}")
    out = VARIANT_OUT / filename
    img = recolor(Image.open(src))
    img = img.resize((512, 512), Image.Resampling.LANCZOS)
    out.parent.mkdir(parents=True, exist_ok=True)
    img.convert("RGB").save(out, "PNG", optimize=True)
    print(f"Wrote {out}")


def main() -> None:
    for filename in FILES:
        save_variant(filename)


if __name__ == "__main__":
    main()
