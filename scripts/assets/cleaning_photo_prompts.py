#!/usr/bin/env python3
"""Build photorealistic image prompts for cleaning services (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "cleaning-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"

SCENE = {
    "home-commercial-cleaning": "a clean modern Indian home or commercial interior in Srinagar Kashmir",
    "furniture-fabric-cleaning": "a clean modern Indian living room in Srinagar Kashmir",
    "appliance-utility-cleaning": "a clean modern Indian kitchen or utility area in Srinagar Kashmir",
    "post-construction-cleaning": "a recently renovated Indian home interior with light construction dust in Srinagar Kashmir",
}

ACTION = {
    "bathroom-cleaning": "cleaning a bathroom sink, tiles, and fixtures",
    "room-cleaning": "mopping and dusting a home room",
    "shop-cleaning": "mopping and wiping a small retail shop floor and counters",
    "kitchen-cleaning": "cleaning kitchen counters and stove exterior",
    "pantry-cleaning": "cleaning an office pantry counter and sink",
    "restaurant-kitchen-cleaning": "deep cleaning a restaurant kitchen cook line",
    "windows-cleaning": "wiping glass doors and windows until streak-free",
    "floor-cleaning": "mopping or scrubbing tile marble flooring",
    "sofa-cleaning": "shampooing and cleaning a sofa",
    "office-chair-cleaning": "cleaning an office chair seat and backrest",
    "mattress-cleaning": "cleaning a mattress surface with professional tools",
    "carpet-cleaning": "shampooing a carpet with cleaning equipment",
    "fan-cleaning": "wiping ceiling fan blades carefully",
    "fridge-cleaning": "cleaning refrigerator shelves and door seals",
    "oven-microwave-cleaning": "cleaning the inside of an oven or microwave",
    "chimney-cleaning": "cleaning a kitchen chimney filter and canopy",
    "water-tank-cleaning": "cleaning the interior of an overhead water tank",
    "post-construction-cleaning-service": "removing post-construction dust from floors and fixtures",
}

PRO_LINE = (
    "Kashmiri male professional in a navy blue work polo uniform with a small white embroidered "
    "Panun Kaergar logo on the chest matching the reference logo, only men, natural soft lighting, "
    "photorealistic stock photo style. No floating watermarks, no text overlays, no extra logos in the scene."
)


def thumb_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Indian home interior in Srinagar Kashmir")
    action = ACTION.get(slug, f"performing {name.lower()}")
    return (
        f"Professional close-up photograph of {name.lower()} in progress, {action}, {scene}, "
        f"{PRO_LINE} Shallow depth of field."
    )


def cover_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Indian home interior in Srinagar Kashmir")
    action = ACTION.get(slug, f"performing {name.lower()}")
    return (
        f"Wide landscape professional photograph showing {name.lower()}, {action}, {scene}, "
        f"{PRO_LINE} Natural daylight, home service photography composition."
    )


def load_services() -> list[dict]:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode((require "{CATALOG}")["services"]);'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def pending() -> list[dict]:
    out = []
    for svc in load_services():
        slug = svc["slug"]
        if (ASSETS / f"{slug}-thumbnail.png").is_file() and (ASSETS / f"{slug}-cover.png").is_file():
            continue
        out.append(
            {
                "slug": slug,
                "name": svc["name"],
                "sub_category_slug": svc.get("sub_category_slug", ""),
                "reference_image": LOGO,
                "thumbnail_prompt": thumb_prompt(slug, svc["name"], svc.get("sub_category_slug", "")),
                "cover_prompt": cover_prompt(slug, svc["name"], svc.get("sub_category_slug", "")),
                "thumbnail_path": str(ASSETS / f"{slug}-thumbnail.png"),
                "cover_path": str(ASSETS / f"{slug}-cover.png"),
            }
        )
    return out


def main() -> None:
    ASSETS.mkdir(parents=True, exist_ok=True)
    rows = pending()
    out_path = Path(__file__).resolve().parent / "data" / "cleaning-photo-prompts.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows)} pending photo prompts to {out_path}")


if __name__ == "__main__":
    main()
