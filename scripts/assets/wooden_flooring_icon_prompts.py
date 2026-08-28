#!/usr/bin/env python3
"""Build wooden flooring variant icon prompt list from the carpentry catalog."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "carpentry-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "wooden-flooring-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

VARIANT_SUBJECTS = {
    "laminate-click-lock": "laminate click lock wooden floor plank silhouette",
    "engineered-wood": "engineered wood floor plank layers silhouette",
    "solid-wood": "solid hardwood floor plank silhouette",
    "flooring-with-skirting": "wooden floor with skirting board silhouette",
    "book-site-inspection": "clipboard checklist with magnifying glass site inspection icon",
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


def main() -> None:
    catalog = load_catalog()
    variants = []
    for svc in catalog["services"]:
        if not str(svc.get("slug", "")).startswith("wooden-flooring-"):
            continue
        for var in svc["variants"]:
            key = var["variant_key"]
            subject = VARIANT_SUBJECTS.get(key, f"{var['title'].lower()} carpentry option icon")
            variants.append(
                {
                    "service_slug": svc["slug"],
                    "service_name": svc["name"],
                    "variant_key": key,
                    "title": var["title"],
                    "filename": f"{svc['slug']}-{key}.png",
                    "prompt": f"Variation icon for {svc['name'].lower()}: {subject}. {ICON_STYLE}",
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"variants": variants}, indent=2))
    print(f"Wrote {len(variants)} wooden flooring variant icon prompts to {OUT}")


if __name__ == "__main__":
    main()
