#!/usr/bin/env python3
"""Build photorealistic image prompts for plumbing services (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "plumbing-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"
OUT = Path(__file__).resolve().parent / "data" / "plumbing-photo-prompts.json"

SCENE = {
    "plumbing-installation": "a clean modern Indian home bathroom or kitchen in Srinagar Kashmir",
    "plumbing-repair": "a lived-in Indian home bathroom or wet area in Srinagar Kashmir",
    "plumbing-inspection": "a residential plumbing area in a Srinagar Kashmir home",
}

ACTION = {
    "plumbing-tap-install": "installing a bathroom tap with plumbing tools",
    "plumbing-shower-install": "installing a shower head and mixer carefully",
    "plumbing-basin-install": "installing a wash basin with bottle trap connection",
    "plumbing-toilet-install": "installing a toilet and flush tank securely",
    "plumbing-sink-install": "installing a kitchen sink with connection hose",
    "plumbing-pipe-install": "fitting and joining home water pipes safely",
    "plumbing-drain-install": "installing a floor drain and waste pipe",
    "plumbing-motor-install": "installing a home water motor pump with piping",
    "plumbing-tank-install": "connecting an overhead water tank float valve",
    "plumbing-geyser-connection": "connecting hot and cold water lines to a geyser",
    "plumbing-accessory-install": "installing a shut-off valve on a water line",
    "plumbing-full-bathroom-plumbing": "inspecting and planning full bathroom plumbing layout",
    "plumbing-full-kitchen-plumbing": "inspecting and planning full kitchen plumbing layout",
    "plumbing-full-house-plumbing": "surveying whole-home plumbing points with checklist",
    "plumbing-tap-repair": "repairing a leaking bathroom tap with wrench",
    "plumbing-shower-repair": "repairing a faulty shower head or jet spray",
    "plumbing-basin-repair": "fixing a leaking bottle trap under a wash basin",
    "plumbing-toilet-repair": "repairing a toilet flush tank mechanism",
    "plumbing-drain-repair": "clearing a blocked kitchen or bathroom drain",
    "plumbing-pipe-repair": "repairing a leaking or damaged water pipe joint",
    "plumbing-leak-repair": "tracing and fixing a visible water leak",
    "plumbing-pressure-repair": "diagnosing low water pressure at a tap",
    "plumbing-motor-repair": "repairing a home water motor that is not starting",
    "plumbing-tank-repair": "fixing an overflowing overhead tank float valve",
    "plumbing-geyser-connection-repair": "fixing a leaking geyser water inlet connection",
    "plumbing-site-check": "inspecting an unknown plumbing fault with tools",
    "plumbing-safety-check": "performing a home plumbing safety inspection",
    "plumbing-pre-work-check": "surveying house plumbing before renovation with checklist",
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
    print(f"Wrote {len(rows)} plumbing photo prompts to {OUT}")


if __name__ == "__main__":
    main()
