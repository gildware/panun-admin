#!/usr/bin/env python3
"""Post-process prompt-generated wooden flooring variant icons."""

from __future__ import annotations

import json
import sys
from pathlib import Path

from PIL import Image

BRAND = (26, 35, 58)
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
VARIANT_OUT = ROOT / "assets" / "variant-icons"
PROMPTS = ROOT / "assets" / "data" / "wooden-flooring-icon-prompts.json"


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
    if not PROMPTS.is_file():
        raise SystemExit(f"Missing prompts file: {PROMPTS}")

    data = json.loads(PROMPTS.read_text())
    missing: list[str] = []
    for row in data["variants"]:
        if not (SRC / row["filename"]).is_file():
            missing.append(row["filename"])
            continue
        save_variant(row["filename"])

    if missing:
        print("MISSING AI icons:", ", ".join(missing), file=sys.stderr)
        sys.exit(2)


if __name__ == "__main__":
    main()
