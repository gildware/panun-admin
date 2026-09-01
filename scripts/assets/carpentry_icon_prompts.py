#!/usr/bin/env python3
"""AI icon prompts for carpentry categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "carpentry-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "carpentry-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "carpentary": (
        "Category icon: crossed hammer and saw with gear, professional carpentry services. " + ICON_STYLE
    ),
    "carpentry-installation": (
        "Category icon: wooden door being fitted with screwdriver, carpentry installation. " + ICON_STYLE
    ),
    "carpentry-making": (
        "Category icon: carpenter saw and wooden plank for custom furniture making. " + ICON_STYLE
    ),
    "carpentry-repairs": (
        "Category icon: wooden chair with wrench and magnifying glass, carpentry repairs. " + ICON_STYLE
    ),
    "roofing-works": (
        "Category icon: pitched wooden roof with beam structure, roofing works. " + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "standard-door": "standard hinged wooden door silhouette",
    "sliding-door": "sliding wooden door on track silhouette",
    "standard-window": "standard hinged wooden window silhouette",
    "sliding-window": "sliding wooden window on track silhouette",
    "single-bed-install": "single bed frame with assemble arrow icon",
    "single-bed-uninstall": "single bed frame with dismantle arrow icon",
    "single-bed-uninstall-install": "single bed with uninstall and install cycle arrows",
    "double-bed-install": "double bed frame with assemble arrow icon",
    "double-bed-uninstall": "double bed frame with dismantle arrow icon",
    "double-bed-uninstall-install": "double bed with uninstall and install cycle arrows",
    "table-install": "wooden table with assemble arrow icon",
    "table-uninstall": "wooden table with dismantle arrow icon",
    "table-install-uninstall": "wooden table with install uninstall cycle arrows",
    "standard-rod": "single curtain rod with wall brackets silhouette",
    "double-rod": "double curtain rod with two parallel bars silhouette",
    "curtain-track": "ceiling curtain track rail with gliders silhouette",
    "rod-uninstall": "curtain rod with dismantle arrow icon",
    "uninstall-install": "curtain rod with uninstall and install cycle arrows",
    "book-on-site-inspection": "clipboard with checklist and magnifying glass site inspection icon",
    "book-site-inspection": "clipboard with checklist and magnifying glass site inspection icon",
    "laminate-click-lock": "laminate click lock wooden floor plank silhouette",
    "engineered-wood": "engineered wood floor plank layers silhouette",
    "solid-wood": "solid hardwood floor plank silhouette",
    "flooring-with-skirting": "wooden floor with skirting board silhouette",
    "loose-lifting-plank": "lifting wooden floor plank silhouette",
    "scratch-dent": "scratched wooden floor plank silhouette",
    "gap-clicking": "wooden floor gap between planks silhouette",
    "water-damage": "water damaged wooden floor plank silhouette",
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
    subject = VARIANT_SUBJECTS.get(variant_key, f"{variant_title} carpentry option icon")
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
