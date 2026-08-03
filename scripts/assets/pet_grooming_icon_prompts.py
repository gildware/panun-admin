#!/usr/bin/env python3
"""AI icon prompts for Pet Grooming categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "pet-grooming-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "pet-grooming-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "pet-grooming": (
        "Category icon: dog and cat silhouette with grooming comb and scissors, professional pet grooming. "
        + ICON_STYLE
    ),
    "dog-grooming": (
        "Category icon: friendly dog face with grooming comb and scissors, dog grooming service. " + ICON_STYLE
    ),
    "cat-grooming": (
        "Category icon: cat face with grooming brush, cat grooming service. " + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "small": "small dog silhouette, compact pet size up to 10 kg",
    "medium": "medium dog silhouette, mid-size pet 10 to 25 kg",
    "large": "large dog silhouette, big pet 25 to 40 kg",
    "extra-large": "extra large dog silhouette, giant breed over 40 kg",
    "short-hair": "short-haired cat silhouette with smooth coat",
    "long-hair": "long-haired cat silhouette with fluffy coat",
    "short-coat": "short coat dog icon with smooth fur",
    "long-coat": "long double coat dog icon with fluffy fur",
    "standard": "single paw print icon representing one standard pet session",
    "puppy": "small puppy dog icon under 6 months",
    "kitten": "small kitten cat icon under 6 months",
    "mild-mats": "pet with small tangle knot icon, mild matting",
    "severe-mats": "pet with heavy matted fur icon, severe matting",
}


def load_catalog() -> dict:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode(require "{CATALOG}");'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def main() -> None:
    catalog = load_catalog()
    categories = []
    for slug, prompt in CATEGORY_PROMPTS.items():
        categories.append({"slug": slug, "filename": f"{slug}.png", "prompt": prompt})

    variants = []
    for svc in catalog["services"]:
        slug = svc["slug"]
        for var in svc["variants"]:
            key = var["variant_key"]
            title = var["title"]
            subject = VARIANT_SUBJECTS.get(key, f"{title.lower()} pet grooming silhouette")
            variants.append(
                {
                    "slug": slug,
                    "variant_key": key,
                    "filename": f"{slug}-{key}.png",
                    "prompt": f"Variation icon for pet grooming: {subject} for {title}. {ICON_STYLE}",
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"categories": categories, "variants": variants}, indent=2) + "\n")
    print(f"Wrote {len(categories)} category + {len(variants)} variant icon prompts to {OUT}")


if __name__ == "__main__":
    main()
