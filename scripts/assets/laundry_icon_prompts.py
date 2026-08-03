#!/usr/bin/env python3
"""AI icon prompts for laundry categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "laundry-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "laundry-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "laundry": (
        "Category icon: hanging shirt and laundry basket representing dry cleaning and laundry services. "
        + ICON_STYLE
    ),
    "wash-laundry": (
        "Category icon: washing machine and folded clothes stack representing laundry wash services. "
        + ICON_STYLE
    ),
    "dry-clean": (
        "Category icon: suit jacket on hanger with sparkle representing garment dry cleaning. "
        + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "wash-fold-per-kg": "folded clothes stack with laundry basket representing wash and fold per kg",
    "wash-iron-per-kg": "clothes iron and hung shirt representing wash and iron per kg",
    "bedsheet-single": "single bedsheet folded neatly icon",
    "bedsheet-double": "double bedsheet folded neatly icon",
    "blanket-single": "single blanket folded icon",
    "blanket-double": "double blanket folded thick icon",
    "comforter-quilt-single": "single comforter quilt puff icon",
    "comforter-quilt-double": "double comforter quilt puff icon",
    "curtain": "window curtain panel hanging icon",
    "pillow-cover": "pillow cover rectangular cushion icon",
    "towel-small": "small folded hand towel icon",
    "towel-large": "large bath towel folded icon",
    "sneakers": "sneaker shoe side profile icon",
    "leather-shoes": "leather formal shoe icon",
    "sports-shoes": "sports running shoe icon",
    "boots": "ankle boot shoe icon",
    "school-bag": "school backpack bag icon",
    "backpack": "hiking backpack bag icon",
    "handbag": "handbag purse icon",
    "laptop-bag": "laptop messenger bag icon",
    "shirt": "button shirt on hanger icon",
    "t-shirt": "t-shirt flat icon",
    "trouser-jeans": "trousers jeans pair icon",
    "kurta-kurti": "kurta garment on hanger icon",
    "salwar-suit": "salwar suit set icon",
    "saree": "folded saree drape icon",
    "suit-2-piece": "two piece suit jacket and trouser icon",
    "suit-3-piece": "three piece suit with waistcoat icon",
    "blazer": "blazer jacket icon",
    "jacket": "casual jacket icon",
    "coat": "long overcoat icon",
    "sweater": "knitted sweater icon",
    "lehenga": "lehenga skirt flare icon",
    "sherwani": "sherwani traditional coat icon",
    "blanket": "folded blanket icon",
    "comforter-quilt": "comforter quilt icon",
}


def load_catalog() -> dict:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode(require "{CATALOG}");'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def variant_prompt(service_name: str, variant_key: str, variant_title: str) -> str:
    subject = VARIANT_SUBJECTS.get(variant_key, f"{variant_title} laundry option icon")
    return f"Variation icon for {service_name.lower()}: {subject}. {ICON_STYLE}"


def main() -> None:
    catalog = load_catalog()
    categories = []
    for slug, prompt in CATEGORY_PROMPTS.items():
        categories.append({"slug": slug, "filename": f"{slug}.png", "prompt": prompt})

    variants = []
    for svc in catalog["services"]:
        for var in svc["variants"]:
            key = var["variant_key"]
            filename = f"{svc['slug']}-{key}.png"
            variants.append(
                {
                    "service_slug": svc["slug"],
                    "service_name": svc["name"],
                    "variant_key": key,
                    "variant_title": var["title"],
                    "filename": filename,
                    "prompt": variant_prompt(svc["name"], key, var["title"]),
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"categories": categories, "variants": variants}, indent=2))
    print(f"Wrote {len(categories)} category + {len(variants)} variant prompts to {OUT}")


if __name__ == "__main__":
    main()
