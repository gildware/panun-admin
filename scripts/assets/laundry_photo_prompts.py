#!/usr/bin/env python3
"""Build photorealistic image prompts for laundry services (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "laundry-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"

SCENE = {
    "wash-laundry": "a clean modern Indian laundry workspace or home service setting in Srinagar Kashmir",
    "dry-clean": "a clean modern Indian dry-cleaning finishing area in Srinagar Kashmir",
}

ACTION = {
    "clothing-laundry": "sorting freshly washed clothes and folding laundry neatly",
    "home-linen-laundry": "handling clean bedsheets blankets and folded home linen",
    "shoe-cleaning": "professionally cleaning sneakers or leather shoes with care tools",
    "bag-cleaning": "cleaning a backpack or handbag exterior carefully",
    "garment-dry-cleaning": "pressing and inspecting a dry-cleaned shirt or suit garment on a hanger",
    "home-linen-dry-cleaning": "handling a dry-cleaned curtain blanket or comforter carefully",
}

PRO_LINE = (
    "Kashmiri male professional in a navy blue work polo uniform with a small white embroidered "
    "Panun Kaergar logo on the chest matching the reference logo (white gear with yellow gear and PANUN KAERGAR text), "
    "only men, natural soft lighting, photorealistic stock photo style. "
    "No floating watermarks, no text overlays, no extra logos in the scene."
)


def thumb_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Indian home service interior in Srinagar Kashmir")
    action = ACTION.get(slug, f"performing {name.lower()}")
    return (
        f"Professional close-up photograph of {name.lower()} in progress, {action}, {scene}, "
        f"{PRO_LINE} Shallow depth of field."
    )


def cover_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Indian home service interior in Srinagar Kashmir")
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
    out_path = Path(__file__).resolve().parent / "data" / "laundry-photo-prompts.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows)} pending photo prompts to {out_path}")


if __name__ == "__main__":
    main()
