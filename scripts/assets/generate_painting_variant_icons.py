#!/usr/bin/env python3
"""Generate ref-style book-site-inspection variant icons for painting services."""

from __future__ import annotations

import importlib.util
import json
import subprocess
from pathlib import Path

from PIL import Image, ImageDraw

SCRIPTS = Path(__file__).resolve().parents[1]
CATALOG = SCRIPTS / "data" / "painting-catalog.php"
VARIANT_IMG = Path(__file__).resolve().parent / "variant-icons"
REPAIR_ICONS = VARIANT_IMG / "generate_repair_variant_icons.py"
SIZE = 512
SCALE = 2


def load_repair():
    spec = importlib.util.spec_from_file_location("repair_icons", REPAIR_ICONS)
    mod = importlib.util.module_from_spec(spec)
    assert spec.loader
    spec.loader.exec_module(mod)
    return mod


def subject_drawer(slug: str, name: str, sub: str, repair):
    n = name.lower()
    if "door" in slug or "gate" in slug:
        return repair.draw_open_door
    if "window" in slug or "grille" in slug:
        return repair.draw_window
    if "wardrobe" in slug or "almirah" in slug:
        return repair.draw_wardrobe
    if "kitchen" in slug or "bathroom" in slug:
        return repair.draw_cabinet
    if "ceiling" in slug or "pop" in slug:
        return repair.draw_roof
    if "texture" in slug or "panel" in slug:
        return repair.draw_panels
    if sub == "exterior-painting" and ("building" in slug or "full-house" in slug or "boundary" in slug):
        return repair.draw_roof
    if "full-room" in slug or "full-house-interior" in slug or "accent" in slug:
        return repair.draw_furniture
    if "consultation" in slug:
        return repair.draw_open_door
    return repair.draw_panels


def save_icon(path: Path, drawer) -> None:
    big = SIZE * SCALE
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    drawer(ImageDraw.Draw(img))
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    path.parent.mkdir(parents=True, exist_ok=True)
    img.convert("RGB").save(path, "PNG", optimize=True)


def main() -> None:
    repair = load_repair()
    catalog = json.loads(
        subprocess.check_output(
            ["php", "-r", f"echo json_encode(require '{CATALOG}');"],
            text=True,
        )
    )
    for svc in catalog["services"]:
        slug = svc["slug"]
        compose = repair.compose(subject_drawer(slug, svc["name"], svc["sub_category_slug"], repair))
        path = VARIANT_IMG / f"{slug}-book-site-inspection.png"
        save_icon(path, compose)
        print(f"Wrote {path.name}")


if __name__ == "__main__":
    main()
