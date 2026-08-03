#!/usr/bin/env python3
"""Post-process AI-generated masonry category + variant icons."""

from __future__ import annotations

import json
import shutil
import subprocess
import sys
from pathlib import Path

from PIL import Image

BRAND = (26, 35, 58)
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
CAT_SRC = ROOT / "assets" / "category-icons"
VARIANT_OUT = ROOT / "assets" / "variant-icons"
PROMPTS = ROOT / "assets" / "data" / "masonry-icon-prompts.json"


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


def resolve_src(filename: str, variant_key: str | None = None) -> Path | None:
    direct = SRC / filename
    if direct.is_file():
        return direct
    if variant_key:
        shared = SRC / f"{variant_key}.png"
        if shared.is_file():
            return shared
        # Reuse a previously prepared service-specific icon for the same variant key.
        for candidate in SRC.glob(f"*-{variant_key}.png"):
            return candidate
    return None


def save_category(slug: str) -> None:
    src = resolve_src(f"{slug}.png")
    if src is None:
        raise SystemExit(f"Missing AI category icon: {SRC / f'{slug}.png'}")
    dest = CAT_SRC / f"{slug}.png"
    dest.parent.mkdir(parents=True, exist_ok=True)
    img = recolor(Image.open(src))
    img = img.resize((512, 512), Image.Resampling.LANCZOS)
    img.convert("RGB").save(dest, "PNG", optimize=True)
    print(f"Wrote {dest}")


def save_variant(filename: str, variant_key: str) -> None:
    src = resolve_src(filename, variant_key)
    if src is None:
        raise SystemExit(f"Missing AI variant icon: {SRC / filename} (or shared {variant_key}.png)")
    # Materialize shared source under expected filename for reuse.
    target_src = SRC / filename
    if src.resolve() != target_src.resolve():
        shutil.copy2(src, target_src)
        src = target_src
    out = VARIANT_OUT / filename
    img = recolor(Image.open(src))
    img = img.resize((512, 512), Image.Resampling.LANCZOS)
    out.parent.mkdir(parents=True, exist_ok=True)
    img.convert("RGB").save(out, "PNG", optimize=True)
    print(f"Wrote {out}")


def main() -> None:
    if not PROMPTS.is_file():
        subprocess.run([sys.executable, str(ROOT / "assets" / "masonry_icon_prompts.py")], check=True)

    data = json.loads(PROMPTS.read_text())
    missing: list[str] = []

    for row in data["categories"]:
        if resolve_src(row["filename"]) is None:
            missing.append(row["filename"])
            continue
        save_category(row["slug"])

    for row in data["variants"]:
        if resolve_src(row["filename"], row["variant_key"]) is None:
            missing.append(row["filename"])
            continue
        save_variant(row["filename"], row["variant_key"])

    if missing:
        print("MISSING AI icons:", ", ".join(missing), file=sys.stderr)
        sys.exit(2)

    print("Done. Run make_theme_pairs for category light/dark next if needed.")


if __name__ == "__main__":
    main()
