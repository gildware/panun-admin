#!/usr/bin/env python3
"""Build photorealistic image prompts for carpentry services (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "carpentry-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"

SCENE = {
    "carpentry-installation": "a clean modern Indian home interior in Srinagar Kashmir",
    "carpentry-making": "a home workshop or unfinished woodwork area in a Srinagar Kashmir home",
    "carpentry-repairs": "a lived-in Indian home interior in Srinagar Kashmir",
    "roofing-works": "a residential rooftop or attic timber area in Srinagar Kashmir",
}

ACTION = {
    "door-installation": "fitting and aligning a wooden door into a frame with carpentry tools",
    "window-installation": "fitting a wooden window into an opening with careful alignment",
    "bed-installation": "assembling a wooden bed frame with tools",
    "table-installation": "assembling a wooden table with tools",
    "bed-making": "measuring and crafting a custom wooden bed frame",
    "wardrobe-making": "building and fitting a custom wooden wardrobe",
    "almirah-making": "crafting and fitting a wooden almirah cupboard",
    "table-making": "building a custom wooden table on site",
    "shop-shelves-making": "installing wooden shop shelves and display racks",
    "kitchen-cabinet-making": "fitting custom kitchen cabinet carcasses",
    "custom-carpentry-work": "performing custom woodworking with saw and measuring tools",
    "door-repair": "repairing a wooden door hinge and alignment",
    "furniture-repair": "repairing a wooden chair or table joint",
    "kitchen-cabinet-repair": "fixing a kitchen cabinet door hinge",
    "wardrobe-repair": "adjusting a wardrobe door or sliding track",
    "window-repair": "repairing a wooden window sash and fittings",
    "other-carpentry-repair": "repairing miscellaneous woodwork with tools",
    "roof-installation": "fitting wooden roof beams and structure",
    "roof-repair": "repairing damaged wooden roof supports",
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
    # Always write full catalog prompts (not only pending) for generation workflow
    all_rows = []
    for svc in load_services():
        slug = svc["slug"]
        all_rows.append(
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
    out_path = Path(__file__).resolve().parent / "data" / "carpentry-photo-prompts.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(all_rows, indent=2))
    print(f"Wrote {len(all_rows)} photo prompts ({len(rows)} pending) to {out_path}")


if __name__ == "__main__":
    main()
