#!/usr/bin/env python3
"""Build photorealistic image prompts for pest control services."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "pest-control-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")

SCENE = {
    "home-pest-control": "a clean modern Indian apartment or home interior",
    "office-pest-control": "a modern Indian office workspace with desks and computers",
    "restaurant-pest-control": "a professional Indian restaurant kitchen or dining area",
}


def thumb_prompt(name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Indian home or commercial space")
    return (
        f"Professional close-up photograph of {name.lower()} in progress, {scene}, "
        "licensed pest control technician in uniform using spray equipment or gel bait, "
        "natural soft lighting, shallow depth of field, photorealistic stock photo style. "
        "No text, no logos, no watermarks."
    )


def cover_prompt(name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Indian home or commercial space")
    return (
        f"Wide landscape professional photograph showing {name.lower()}, {scene}, "
        "pest control service technician treating the premises, natural daylight, "
        "photorealistic home service photography. No text, no logos, no watermarks."
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
                "thumbnail_prompt": thumb_prompt(svc["name"], svc.get("sub_category_slug", "")),
                "cover_prompt": cover_prompt(svc["name"], svc.get("sub_category_slug", "")),
                "thumbnail_path": str(ASSETS / f"{slug}-thumbnail.png"),
                "cover_path": str(ASSETS / f"{slug}-cover.png"),
            }
        )
    return out


def main() -> None:
    ASSETS.mkdir(parents=True, exist_ok=True)
    rows = pending()
    out_path = Path(__file__).resolve().parent / "data" / "pest-control-photo-prompts.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows)} prompts to {out_path}")


if __name__ == "__main__":
    main()
