#!/usr/bin/env python3
"""AI icon prompts for masonry categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "masonry-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "masonry-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "masonry": (
        "Category icon: brick wall with trowel and spirit level, professional masonry services. " + ICON_STYLE
    ),
    "masonry-installation": (
        "Category icon: brick with plus install mark and trowel, masonry installation. " + ICON_STYLE
    ),
    "masonry-repair": (
        "Category icon: cracked wall with trowel and patch, masonry repairs. " + ICON_STYLE
    ),
    "masonry-inspection": (
        "Category icon: clipboard checklist with brick and magnifying glass, masonry inspection. " + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "book-inspection": "clipboard with checklist and magnifying glass site inspection icon",
    "crack-check": "magnifying glass over cracked wall check silhouette",
    "damp-check": "magnifying glass over damp wall stain check silhouette",
    "unknown-problem": "question mark with brick wall silhouette",
    "full-home-masonry-check": "home shield with brick wall safety check silhouette",
    "before-renovation-check": "renovation house masonry survey clipboard silhouette",
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
    subject = VARIANT_SUBJECTS.get(variant_key, f"{variant_title} masonry option icon")
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
