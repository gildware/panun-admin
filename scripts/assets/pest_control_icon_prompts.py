#!/usr/bin/env python3
"""AI icon prompts for pest control categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "pest-control-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "pest-control-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "pest-control": (
        "Category icon: protective shield with cockroach and spray nozzle, representing professional pest control service. "
        + ICON_STYLE
    ),
    "home-pest-control": (
        "Category icon: Indian home house silhouette with small cockroach and shield, home pest control. "
        + ICON_STYLE
    ),
    "office-pest-control": (
        "Category icon: office building with desks and cockroach shield, commercial office pest control. "
        + ICON_STYLE
    ),
    "restaurant-pest-control": (
        "Category icon: restaurant kitchen hood and dining table with pest shield, food business pest control. "
        + ICON_STYLE
    ),
}


def variant_prompt(service_name: str, variant_title: str, variant_key: str) -> str:
    subject = {
        "1-bhk": "Indian apartment building floor plan icon showing one bedroom layout",
        "2-bhk": "Indian apartment building icon showing two bedroom layout",
        "3-bhk": "Indian apartment building icon showing three bedroom layout",
        "4-bhk": "Indian apartment building icon showing four bedroom layout",
        "2000-3000-sq-ft": "large Indian bungalow home icon medium size",
        "3000-4000-sq-ft": "large Indian bungalow home icon",
        "4000-5000-sq-ft": "very large Indian bungalow home icon",
        "5000-sq-ft": "extra large luxury bungalow home icon",
        "1-bathroom-and-kitchen": "home kitchen and bathroom combined rooms icon",
        "kitchen-only": "home kitchen room with cabinets and stove icon",
        "1-bedroom-kitchen": "one bedroom plus kitchen home layout icon",
        "2-bedroom-kitchen": "two bedroom plus kitchen home layout icon",
        "3-bedroom-kitchen": "three bedroom plus kitchen home layout icon",
        "4-bedroom-kitchen": "four bedroom plus kitchen home layout icon",
        "balcony": "home balcony railing outdoor space icon",
        "bathroom": "bathroom with bathtub and shower icon",
        "bedroom": "bedroom with bed and pillow icon",
        "extra-room": "additional spare room with door icon",
        "not-required": "circle with X mark meaning optional add-on not selected",
        "yes": "circle with checkmark meaning optional add-on selected",
        "up-to-500-sq-ft": "small office floor plan icon labeled small area",
        "500-1000-sq-ft": "medium office floor plan icon",
        "1000-2000-sq-ft": "large office floor plan icon",
        "2000-sq-ft": "extra large office floor plan icon XL",
        "small-kitchen": "small commercial restaurant kitchen icon",
        "medium-kitchen": "medium commercial restaurant kitchen icon",
        "large-kitchen": "large commercial restaurant kitchen icon",
        "kitchen-storage": "restaurant kitchen with storage pantry icon",
        "up-to-20-seats": "small restaurant dining with few tables icon",
        "21-50-seats": "medium restaurant dining room with multiple tables icon",
        "51-100-seats": "large restaurant dining hall icon",
        "100-plus-seats": "very large restaurant banquet dining hall icon",
    }.get(variant_key, f"{variant_title} for {service_name}")

    if "rodent" in service_name.lower() and variant_key.startswith(("up-to", "500", "1000", "2000")):
        subject = f"office floor plan with mouse trap icon, {variant_title}"
    if "ant" in service_name.lower() and variant_key.startswith(("up-to", "500", "1000", "2000")):
        subject = f"office floor plan with ant trail icon, {variant_title}"

    return f"Variation icon for pest control: {subject}. {ICON_STYLE}"


def load_services() -> list[dict]:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode((require "{CATALOG}")["services"]);'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def main() -> None:
    rows = {"categories": [], "variants": []}
    for slug, prompt in CATEGORY_PROMPTS.items():
        rows["categories"].append({"slug": slug, "filename": f"{slug}.png", "prompt": prompt})

    for svc in load_services():
        slug = svc["slug"]
        name = svc["name"]
        for var in svc["variants"]:
            key = var["variant_key"]
            rows["variants"].append(
                {
                    "slug": slug,
                    "variant_key": key,
                    "filename": f"{slug}-{key}.png",
                    "prompt": variant_prompt(name, var["title"], key),
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows['categories'])} category + {len(rows['variants'])} variant prompts -> {OUT}")


if __name__ == "__main__":
    main()
