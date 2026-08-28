#!/usr/bin/env python3
"""AI icon prompts for Generators subcategory + variants."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "home-appliances-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "generators-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPT = (
    "Category icon: portable petrol generator silhouette with handle and outlet panel, home generator services. "
    + ICON_STYLE
)

VARIANT_SUBJECTS = {
    "petrol-upto-3kva": "small portable petrol generator up to 3 kVA silhouette",
    "petrol-3-to-5kva": "medium petrol generator 3 to 5 kVA silhouette",
    "diesel-upto-10kva": "home diesel generator up to 10 kVA silhouette",
    "diesel-10-to-20kva": "shop diesel generator 10 to 20 kVA silhouette",
    "diesel-above-20kva": "large diesel generator above 20 kVA silhouette",
    "book-site-inspection": "clipboard checklist with magnifying glass site inspection icon",
    "wont-start": "generator that will not start with spark plug silhouette",
    "no-power-output": "generator with no electricity output plug silhouette",
    "noise-smoke": "generator with noise waves and smoke silhouette",
    "fuel-oil-leak": "generator fuel or oil leak drip silhouette",
    "petrol-upto-5kva": "petrol generator servicing oil can and filter silhouette",
    "petrol": "portable petrol generator removal silhouette",
    "diesel-above-10kva": "larger diesel generator uninstall silhouette",
}


def load_catalog() -> dict:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode(require "{CATALOG}");'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def variant_prompt(service_name: str, variant_key: str, title: str) -> str:
    subject = VARIANT_SUBJECTS.get(variant_key, f"{title.lower()} generator service silhouette")
    return f"Variation icon for {service_name.lower()}: {subject}. {ICON_STYLE}"


def main() -> None:
    catalog = load_catalog()
    categories = [{"slug": "generators", "filename": "generators.png", "prompt": CATEGORY_PROMPT}]
    variants = []
    for svc in catalog["services"]:
        if svc.get("sub_category_slug") != "generators":
            continue
        for var in svc["variants"]:
            key = var["variant_key"]
            filename = f"{svc['slug']}-{key}.png"
            variants.append(
                {
                    "service_slug": svc["slug"],
                    "service_name": svc["name"],
                    "variant_key": key,
                    "title": var["title"],
                    "filename": filename,
                    "prompt": variant_prompt(svc["name"], key, var["title"]),
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"categories": categories, "variants": variants}, indent=2))
    print(f"Wrote {len(categories)} category + {len(variants)} variant icon prompts to {OUT}")


if __name__ == "__main__":
    main()
