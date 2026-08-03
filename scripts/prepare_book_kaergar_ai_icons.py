#!/usr/bin/env python3
"""Post-process AI-generated Book Kaergar category + variant icons to pure #1A233A 512px."""

from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

from PIL import Image

BRAND = (0x1A, 0x23, 0x3A, 255)
WHITE = (255, 255, 255, 255)
SIZE = 512
PAD = 0.10

SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
CAT_SRC = ROOT / "assets" / "category-icons"
VARIANT_OUT = ROOT / "assets" / "variant-icons"
PROMPTS = ROOT / "assets" / "data" / "book-kaergar-icon-prompts.json"


def force_navy(img: Image.Image) -> Image.Image:
    img = img.convert("RGBA")
    px = img.load()
    w, h = img.size
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            if a < 20:
                px[x, y] = (0, 0, 0, 0)
                continue
            # treat near-white as background
            if r > 230 and g > 230 and b > 230:
                px[x, y] = (0, 0, 0, 0)
                continue
            # any visible ink -> pure brand navy
            px[x, y] = BRAND
    return img


def crop_center(img: Image.Image) -> Image.Image:
    img = img.convert("RGBA")
    alpha = img.split()[-1]
    bbox = alpha.getbbox()
    if not bbox:
        return img
    cropped = img.crop(bbox)
    cw, ch = cropped.size
    side = max(cw, ch)
    pad = int(side * PAD)
    canvas = Image.new("RGBA", (side + 2 * pad, side + 2 * pad), (0, 0, 0, 0))
    ox = (canvas.size[0] - cw) // 2
    oy = (canvas.size[1] - ch) // 2
    canvas.paste(cropped, (ox, oy), cropped)
    return canvas.resize((SIZE, SIZE), Image.Resampling.LANCZOS)


def save_png(path: Path, img: Image.Image, *, transparent: bool = True) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    if transparent:
        img.save(path, "PNG", optimize=True)
    else:
        bg = Image.new("RGB", img.size, (255, 255, 255))
        bg.paste(img, mask=img.split()[-1])
        bg.save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def process_file(filename: str, dest: Path) -> None:
    src = SRC / filename
    if not src.is_file():
        raise FileNotFoundError(str(src))
    img = force_navy(Image.open(src))
    img = crop_center(img)
    # variant upload path expects opaque white-bg PNG historically; keep transparent ok for uploader
    save_png(dest, img, transparent=True)


def main() -> None:
    if not PROMPTS.is_file():
        subprocess.run([sys.executable, str(ROOT / "assets" / "book_kaergar_icon_prompts.py")], check=True)

    data = json.loads(PROMPTS.read_text())
    missing: list[str] = []

    for row in data.get("categories", []):
        # variants-only runs may skip categories
        src = SRC / row["filename"]
        if not src.is_file():
            continue
        process_file(row["filename"], CAT_SRC / row["filename"])

    for row in data["variants"]:
        src = SRC / row["filename"]
        if not src.is_file():
            missing.append(row["filename"])
            continue
        process_file(row["filename"], VARIANT_OUT / row["filename"])

    if missing:
        print("MISSING AI icons:", ", ".join(missing), file=sys.stderr)
        sys.exit(2)

    print(f"Done. Processed {len(data['variants'])} Book Kaergar variant icons.")


if __name__ == "__main__":
    main()
