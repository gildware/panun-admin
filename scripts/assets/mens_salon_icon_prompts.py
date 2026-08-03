#!/usr/bin/env python3
"""AI icon prompts for Men's Salon categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "mens-salon-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "mens-salon-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "mens-salon": (
        "Category icon: men's scissors and comb with neat beard silhouette, men's salon at home. " + ICON_STYLE
    ),
    "mens-hair-services": (
        "Category icon: hair clippers and comb, men's haircut and hair services. " + ICON_STYLE
    ),
    "mens-beard-shaving": (
        "Category icon: beard silhouette with trimmer and razor, men's beard and shaving. " + ICON_STYLE
    ),
    "mens-skin-grooming-care": (
        "Category icon: male face with spa leaf and grooming brush, men's skin and grooming. " + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "standard-hair-cut": "men haircut scissors and comb silhouette",
    "premium-hair-cut": "premium men haircut clippers and styling comb silhouette",
    "kids-hair-cut": "boy haircut scissors and small comb silhouette",
    "hair-color-with-product": "hair dye brush and color bowl silhouette",
    "hair-color-without-product": "hair color application brush only silhouette",
    "standard-hair-treatment": "hair treatment bottle and scalp massage hands silhouette",
    "premium-hair-treatment": "premium hair mask jar and treatment brush silhouette",
    "beard-trim": "beard trimmer and beard outline silhouette",
    "beard-mustache-trim": "beard and mustache trim silhouette",
    "standard-clean-shave": "safety razor and shaving foam brush silhouette",
    "premium-clean-shave": "straight razor and hot towel silhouette",
    "beard-color-with-product": "beard dye brush and small color tube silhouette",
    "beard-color-without-product": "beard color application brush silhouette",
    "face-neck-detan": "male face and neck with sponge pad silhouette",
    "hands-detan": "hands with detan cream pad silhouette",
    "underarm-waxing": "underarm wax strip silhouette",
    "chest-waxing": "male chest wax strip silhouette",
    "back-waxing": "male back wax strip silhouette",
    "full-arms-waxing": "arm wax strip silhouette",
    "instant-cleanup": "face cleanup sponge and cotton pad silhouette",
    "deep-cleanup-facial": "facial bowl brush and steam face silhouette",
    "eyebrow-threading": "eyebrow threading thread loop silhouette",
    "full-face-threading": "full face threading outline silhouette",
    "express-pedicure": "male foot and nail file silhouette",
    "express-manicure": "male hand and nail clipper silhouette",
    "hands-nail-cut-file": "hand nail cut and file silhouette",
    "feet-nail-cut-file": "foot nail cut and file silhouette",
    "head-massage-10-min": "head massage hands on scalp silhouette 10 minute",
    "head-massage-20-min": "head massage hands on scalp silhouette 20 minute",
    "head-neck-shoulder-massage": "neck and shoulder massage hands silhouette",
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
            subject = VARIANT_SUBJECTS.get(key, f"{title.lower()} grooming silhouette")
            variants.append(
                {
                    "slug": slug,
                    "variant_key": key,
                    "filename": f"{slug}-{key}.png",
                    "prompt": (
                        f"Variation icon for men's salon: {subject} for {title}. {ICON_STYLE}"
                    ),
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"categories": categories, "variants": variants}, indent=2))
    print(f"Wrote {len(categories)} category + {len(variants)} variant icon prompts to {OUT}")


if __name__ == "__main__":
    main()
