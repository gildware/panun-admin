#!/usr/bin/env python3
"""
Variant icons for missing catalog — same format as Carpentry Repairs:
  {slug}-book-site-inspection.png  (ref-style #1A233A compose)
  {slug}-{variant_key}.png         (same icon for each variant row)
"""

from __future__ import annotations

import importlib.util
import json
import re
import shutil
from pathlib import Path

SCRIPTS = Path(__file__).resolve().parents[1]
MANIFEST = SCRIPTS / "data" / "missing-catalog-manifest.json"
VARIANT_IMG = Path(__file__).resolve().parent / "variant-icons"
SIZE = 512
SCALE = 2


def load_repair_icons():
    path = VARIANT_IMG / "generate_repair_variant_icons.py"
    spec = importlib.util.spec_from_file_location("repair_icons", path)
    mod = importlib.util.module_from_spec(spec)
    assert spec.loader
    spec.loader.exec_module(mod)
    return mod


REPAIR = load_repair_icons()


def load_remaining_icons():
    path = VARIANT_IMG / "generate_remaining_variant_icons.py"
    spec = importlib.util.spec_from_file_location("remaining_icons", path)
    mod = importlib.util.module_from_spec(spec)
    assert spec.loader
    spec.loader.exec_module(mod)
    return mod


REMAINING = load_remaining_icons()


def save_icon(path: Path, drawer) -> None:
    big = SIZE * SCALE
    from PIL import Image, ImageDraw

    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    drawer(ImageDraw.Draw(img))
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    path.parent.mkdir(parents=True, exist_ok=True)
    img.convert("RGB").save(path, "PNG", optimize=True)


def subject_drawer(name: str, category_slug: str, sub_slug: str):
    n = name.lower()
    if "door" in n:
        return REPAIR.draw_open_door
    if "furniture" in n or "sofa" in n or "chair" in n or "table" in n or "ottoman" in n or "recliner" in n:
        return REPAIR.draw_furniture
    if "bed" in n or "mattress" in n or "headboard" in n:
        return REMAINING.draw_bed
    if "window" in n:
        return REPAIR.draw_window
    if "wardrobe" in n or "cupboard" in n:
        return REPAIR.draw_wardrobe
    if "kitchen" in n or "cabinet" in n or "chimney" in n:
        return REPAIR.draw_cabinet
    if "panel" in n:
        return REPAIR.draw_panels
    if "roof" in n:
        return REPAIR.draw_roof
    if "carpet" in n:
        return REMAINING.draw_carpet
    if "curtain" in n or "blind" in n:
        return REMAINING.draw_curtain
    if "fan" in n or "mirror" in n:
        return REPAIR.draw_window
    if category_slug == "cleaning":
        if "kitchen" in n:
            return REMAINING.draw_kitchen
        if "tank" in n or "water" in n:
            return REMAINING.draw_tank
        return REMAINING.draw_home
    if category_slug == "electrical":
        if "light" in n or "bulb" in n or "chandelier" in n:
            return REMAINING.draw_bulb
        if "switch" in n or "socket" in n:
            return REMAINING.draw_socket
        return REMAINING.draw_wiring
    if category_slug == "plumbing":
        if "tank" in n or "geyser" in n or "water" in n:
            return REMAINING.draw_tank
        return REMAINING.draw_tank
    if category_slug == "home-appliance":
        if "wash" in n or "laundry" in n:
            return REMAINING.draw_washer
        if "iron" in n:
            return REMAINING.draw_iron
        return REMAINING.draw_washer
    if "salon" in category_slug:
        return REMAINING.draw_garment
    return REPAIR.draw_open_door


def is_inspection_variant(name: str, variant_key: str, variation_label: str) -> bool:
    n = name.lower()
    if re.search(r"\b(book|consultation|carpenter|inspection)\b", n):
        return True
    if variant_key in ("standard", "book-site-inspection"):
        return True
    if variation_label in ("—", "-", ""):
        return True
    return False


def main() -> None:
    data = json.loads(MANIFEST.read_text())
    count = 0
    for svc in data["services"]:
        slug = svc["slug"]
        compose = REPAIR.compose(subject_drawer(svc["name"], svc["category_slug"], svc["sub_slug"]))
        inspection_path = VARIANT_IMG / f"{slug}-book-site-inspection.png"
        save_icon(inspection_path, compose)
        count += 1

        for variant in svc["variants"]:
            out = VARIANT_IMG / f"{slug}-{variant['variant_key']}.png"
            if out != inspection_path:
                shutil.copy2(inspection_path, out)
            count += 1

    print(f"Wrote {count} variant icons (ref-style book-site-inspection format)")


if __name__ == "__main__":
    main()
