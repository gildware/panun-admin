#!/usr/bin/env python3
"""Build photorealistic image prompts for masonry services (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "masonry-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"
OUT = Path(__file__).resolve().parent / "data" / "masonry-photo-prompts.json"

SCENE = {
    "masonry-installation": "a residential construction or renovation site in Srinagar Kashmir",
    "masonry-repair": "a lived-in Kashmiri home wall or wet area needing masonry repair in Srinagar",
    "masonry-inspection": "a residential masonry area in a Srinagar Kashmir home",
}

ACTION = {
    "masonry-brick-install": "laying bricks with trowel and spirit level for a wall section",
    "masonry-plaster-install": "applying cement plaster to a wall with a trowel",
    "masonry-tile-install": "laying floor or wall tiles carefully with adhesive",
    "masonry-marble-install": "setting a marble slab floor or cladding carefully",
    "masonry-stone-install": "fitting natural stone cladding on a wall section",
    "masonry-stair-install": "building or finishing a concrete masonry stair step",
    "masonry-waterproof-install": "applying waterproofing coating to a bathroom floor base",
    "masonry-boundary-install": "building a boundary wall brick section outdoors",
    "masonry-full-bathroom-setup": "inspecting and planning full bathroom masonry layout with checklist",
    "masonry-crack-repair": "inspecting and preparing a cracked wall for masonry repair",
    "masonry-plaster-repair": "patching damaged wall plaster carefully",
    "masonry-tile-repair": "replacing a loose or broken floor tile",
    "masonry-marble-repair": "repairing a cracked or uneven marble surface",
    "masonry-stair-repair": "repairing a broken stair edge for safety",
    "masonry-damp-repair": "inspecting a damp wall patch and preparing treatment",
    "masonry-boundary-repair": "repairing a damaged outdoor boundary wall section",
    "masonry-site-check": "inspecting an unknown masonry fault with tools and checklist",
    "masonry-safety-check": "performing a home masonry safety inspection",
    "masonry-pre-work-check": "surveying house masonry before renovation with checklist",
}

PRO_LINE = (
    "Kashmiri male professional in a navy blue work polo uniform with a small white embroidered "
    "Panun Kaergar logo on the chest matching the reference logo (white and gold interlocking gear icon "
    "next to the white text PANUN KAERGAR), only men, natural soft lighting, "
    "photorealistic stock photo style. No floating watermarks, no text overlays, no extra logos in the scene."
)


def thumb_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a residential site in Srinagar Kashmir")
    action = ACTION.get(slug, f"performing {name.lower()}")
    return (
        f"Professional close-up photograph of {name.lower()} in progress, {action}, {scene}, "
        f"{PRO_LINE} Shallow depth of field."
    )


def cover_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a residential site in Srinagar Kashmir")
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


def main() -> None:
    rows = []
    for svc in load_services():
        slug = svc["slug"]
        name = svc["name"]
        sub = svc["sub_category_slug"]
        rows.append(
            {
                "slug": slug,
                "name": name,
                "sub_category_slug": sub,
                "reference_image": LOGO,
                "thumbnail_prompt": thumb_prompt(slug, name, sub),
                "cover_prompt": cover_prompt(slug, name, sub),
                "thumbnail_path": str(ASSETS / f"{slug}-thumbnail.png"),
                "cover_path": str(ASSETS / f"{slug}-cover.png"),
            }
        )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows)} masonry photo prompts to {OUT}")


if __name__ == "__main__":
    main()
