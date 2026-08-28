#!/usr/bin/env python3
"""Build photorealistic image prompts for Generators services."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "home-appliances-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"
OUT = Path(__file__).resolve().parent / "data" / "generators-photo-prompts.json"

SCENE = "a Kashmir home courtyard or shop backyard with a petrol or diesel generator"
PRO_LINE = (
    "Kashmiri male professional in a navy blue work polo uniform with a small white embroidered "
    "Panun Kaergar logo on the chest matching the reference logo, only men, natural soft lighting, "
    "photorealistic stock photo style. No floating watermarks, no text overlays, no extra logos in the scene."
)
ACTION = {
    "generator-installation": "installing a home petrol or diesel generator and checking placement",
    "generator-repair": "diagnosing a generator that will not start, checking fuel and wiring",
    "generator-servicing": "servicing a generator with oil, filter, and engine checks",
    "generator-uninstallation": "safely disconnecting and uninstalling a home generator",
}


def thumb_prompt(slug: str, name: str) -> str:
    action = ACTION.get(slug, f"performing {name.lower()}")
    return (
        f"Professional close-up photograph of {name.lower()} in progress, {action}, {SCENE}, "
        f"{PRO_LINE} Shallow depth of field."
    )


def cover_prompt(slug: str, name: str) -> str:
    action = ACTION.get(slug, f"performing {name.lower()}")
    return (
        f"Wide landscape professional photograph showing {name.lower()}, {action}, {SCENE}, "
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
        if svc.get("sub_category_slug") != "generators":
            continue
        slug = svc["slug"]
        name = svc["name"]
        rows.append(
            {
                "slug": slug,
                "name": name,
                "sub_category_slug": "generators",
                "reference_image": LOGO,
                "thumbnail_prompt": thumb_prompt(slug, name),
                "cover_prompt": cover_prompt(slug, name),
                "thumbnail_path": str(ASSETS / f"{slug}-thumbnail.png"),
                "cover_path": str(ASSETS / f"{slug}-cover.png"),
            }
        )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows)} generator photo prompts to {OUT}")


if __name__ == "__main__":
    main()
