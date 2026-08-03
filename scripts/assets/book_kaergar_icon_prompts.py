#!/usr/bin/env python3
"""AI icon prompts for Book Kaergar category + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "book-kaergar-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "book-kaergar-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon, perfect square 1:1 composition with equal padding. "
    "Solid dark navy blue #1A233A shapes ONLY on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. "
    "No text, no letters, no numbers, no gradients, no shadows, no gray, no outline strokes, centered."
)

CATEGORY_PROMPTS = {
    "book-kaergar": (
        "Category icon: calendar clock with gear and person silhouette for booking a professional by time. "
        + ICON_STYLE
    ),
    "home-trades": (
        "Category icon: crossed hammer wrench screwdriver for home trades carpenter electrician plumber painter. "
        + ICON_STYLE
    ),
    "building-site": (
        "Category icon: hard hat with brick and shovel for building site mason labour welder. " + ICON_STYLE
    ),
    "home-care": (
        "Category icon: house with leaf and broom for home care gardener cleaner. " + ICON_STYLE
    ),
    "beauty-artists": (
        "Category icon: makeup brush and mehndi henna cone for beauty artists. " + ICON_STYLE
    ),
}

# Trade / role subject so the icon matches the service name
ROLE_SUBJECTS = {
    "book-a-carpenter": "claw hammer and handsaw crossed (carpenter tools)",
    "book-an-electrician": "lightning bolt through electrical plug (electrician)",
    "book-a-plumber": "pipe wrench and water faucet (plumber)",
    "book-a-painter": "paint roller and paintbrush crossed (painter)",
    "book-a-mason": "brick wall block and trowel (mason)",
    "book-labour": "shovel and wheelbarrow (site labour)",
    "book-a-welder": "welding torch and metal spark burst (welder fabricator)",
    "book-a-gardener": "leaf and garden watering can (gardener)",
    "book-a-cleaner": "broom and spray bottle (cleaner)",
    "book-makeup-artist": "makeup brush and compact powder (makeup artist)",
    "book-mehndi-artist": "mehndi henna cone and decorated hand outline (mehndi artist)",
}

# Duration cue so hourly / half-day / full-day are distinct
DURATION_CUES = {
    "hourly": "small circular clock badge at bottom-right showing short one-hour hand position",
    "half-day": "small half-sun badge at bottom-right for half day four hours",
    "full-day": "small full sun badge at bottom-right for full day eight hours",
}


def load_catalog() -> dict:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode(require "{CATALOG}");'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def variant_prompt(service_slug: str, service_name: str, variant_key: str, variant_title: str) -> str:
    role = ROLE_SUBJECTS.get(service_slug, f"{service_name} tools")
    duration = DURATION_CUES.get(variant_key, f"booking duration badge for {variant_title}")
    return (
        f"Variation icon named for {service_name}: large centered {role}, "
        f"plus {duration}. Icon must clearly read as {service_name} booking package. "
        f"{ICON_STYLE}"
    )


def main() -> None:
    catalog = load_catalog()
    categories = [{"slug": slug, "filename": f"{slug}.png", "prompt": prompt} for slug, prompt in CATEGORY_PROMPTS.items()]

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
                    "prompt": variant_prompt(svc["slug"], svc["name"], key, var["title"]),
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"categories": categories, "variants": variants}, indent=2))
    print(f"Wrote {len(categories)} category + {len(variants)} variant prompts to {OUT}")


if __name__ == "__main__":
    main()
