#!/usr/bin/env python3
"""Post-process AI-generated carpentry category + variant icons."""

from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

from PIL import Image

BRAND = (26, 35, 58)
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
CAT_SRC = ROOT / "assets" / "category-icons"
VARIANT_OUT = ROOT / "assets" / "variant-icons"
PROMPTS = ROOT / "assets" / "data" / "carpentry-icon-prompts.json"


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


def save_category(slug: str) -> None:
    src = SRC / f"{slug}.png"
    if not src.is_file():
        raise SystemExit(f"Missing AI category icon: {src}")
    dest = CAT_SRC / f"{slug}.png"
    dest.parent.mkdir(parents=True, exist_ok=True)
    img = recolor(Image.open(src))
    img = img.resize((512, 512), Image.Resampling.LANCZOS)
    img.convert("RGB").save(dest, "PNG", optimize=True)
    print(f"Wrote {dest}")


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
        subprocess.run([sys.executable, str(ROOT / "assets" / "carpentry_icon_prompts.py")], check=True)

    data = json.loads(PROMPTS.read_text())
    missing: list[str] = []

    for row in data["categories"]:
        if not (SRC / row["filename"]).is_file():
            missing.append(row["filename"])
            continue
        save_category(row["slug"])

    for row in data["variants"]:
        if str(row.get("service_slug", "")).startswith("wooden-flooring-"):
            continue
        if not (SRC / row["filename"]).is_file():
            missing.append(row["filename"])
            continue
        save_variant(row["filename"])

    if missing:
        print("MISSING AI icons:", ", ".join(missing), file=sys.stderr)
        sys.exit(2)

    print("Done. Run make_theme_pairs for category light/dark next.")


if __name__ == "__main__":
    main()
