#!/usr/bin/env python3
"""AI icon prompts for cleaning categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "cleaning-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "cleaning-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "cleaning": (
        "Category icon: mop and sparkle representing professional cleaning services. " + ICON_STYLE
    ),
    "home-commercial-cleaning": (
        "Category icon: house and shop storefront with mop, home and commercial cleaning. " + ICON_STYLE
    ),
    "furniture-fabric-cleaning": (
        "Category icon: sofa silhouette with soft sparkle, furniture and fabric cleaning. " + ICON_STYLE
    ),
    "appliance-utility-cleaning": (
        "Category icon: ceiling fan and refrigerator outline, appliance and utility cleaning. " + ICON_STYLE
    ),
    "post-construction-cleaning": (
        "Category icon: hard hat and broom over unfinished room, post construction cleaning. " + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "standard-upto-20-sq-ft": "small bathroom tiles and shower representing standard clean up to 20 sq ft",
    "intense-upto-20-sq-ft": "small bathroom with deep scrub brush representing intense clean up to 20 sq ft",
    "standard-upto-50-sq-ft": "larger bathroom floor plan icon representing standard clean up to 50 sq ft",
    "intense-upto-50-sq-ft": "larger bathroom with deep clean brush representing intense clean up to 50 sq ft",
    "home-unfurnished": "empty unfurnished room with bare floor icon",
    "home-furnished": "furnished room with bed and chair icon",
    "shop-upto-200-sq-ft": "small retail shop storefront icon up to 200 sq ft",
    "shop-upto-500-sq-ft": "medium retail shop storefront icon up to 500 sq ft",
    "home-standard": "home kitchen cabinets and stove standard clean icon",
    "home-intense": "home kitchen with grease scrub brush intense clean icon",
    "office-standard": "office pantry counter and kettle standard clean icon",
    "office-intense": "office pantry with deep clean sponge intense icon",
    "restaurant-standard": "restaurant kitchen stove standard clean icon",
    "restaurant-intense": "restaurant kitchen with heavy grease scrub intense icon",
    "glass-doors-windows": "glass door and window panes icon",
    "windows-with-net-glass-doors": "window with mosquito net and glass door icon",
    "tile-marble-mopping-upto-500-sq-ft": "tile floor with mop representing mopping up to 500 sq ft",
    "tile-marble-deep-scrub-upto-500-sq-ft": "marble floor with scrubber machine deep clean icon",
    "leather-5-seater": "leather sofa five seater icon",
    "leather-7-seater": "large leather sofa seven seater icon",
    "fabric-5-seater": "fabric sofa five seater icon",
    "fabric-7-seater": "large fabric sofa seven seater icon",
    "executive-chair": "executive office chair icon",
    "visitor-workstation-chair": "visitor workstation office chair icon",
    "double-mattress": "double bed mattress icon",
    "single-mattress": "single bed mattress icon",
    "upto-10-sq-ft": "small carpet rug up to 10 sq ft icon",
    "upto-50-sq-ft": "medium carpet up to 50 sq ft icon",
    "upto-100-sq-ft": "large carpet up to 100 sq ft icon",
    "table-pedestal-fan": "table pedestal fan icon",
    "ceiling-fan": "ceiling fan blades icon",
    "standard": "standard cleaning sparkle badge icon",
    "intense": "intense deep cleaning scrub brush badge icon",
    "500-ltr": "water tank 500 litre drum icon",
    "1000-ltr": "large water tank 1000 litre drum icon",
    "home-upto-1000-sq-ft": "home floor plan up to 1000 sq ft icon",
    "home-upto-5000-sq-ft": "large home floor plan up to 5000 sq ft icon",
    "office-shop-upto-1000-sq-ft": "office shop floor plan up to 1000 sq ft icon",
    "hotel-restaurant-clinic-upto-1000-sq-ft": "hotel restaurant clinic building up to 1000 sq ft icon",
    "book-on-site-inspection": "clipboard with checklist and magnifying glass site inspection icon",
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
    subject = VARIANT_SUBJECTS.get(variant_key, f"{variant_title} cleaning option icon")
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
