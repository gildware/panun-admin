#!/usr/bin/env python3
"""Build photorealistic image prompts for missing-catalog services."""

from __future__ import annotations

import json
import re
from pathlib import Path

MANIFEST = Path(__file__).resolve().parents[1] / "data" / "missing-catalog-manifest.json"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")

CATEGORY_SCENE = {
    "cleaning": "a clean modern Indian home interior",
    "plumbing": "a modern Indian bathroom or kitchen",
    "electrical": "a modern Indian home with electrical fixtures",
    "home-appliance": "a modern Indian living room or kitchen",
    "mens-salon": "a professional men's salon in India",
    "womens-salon": "a professional women's beauty salon in India",
    "carpentary": "a modern Indian home with woodwork",
}


def scene_for(service: dict) -> str:
    cat = service.get("category_slug", "")
    if cat in CATEGORY_SCENE:
        return CATEGORY_SCENE[cat]
    return "a modern Indian home or service setting"


def thumb_prompt(service: dict) -> str:
    name = service["name"]
    scene = scene_for(service)
    return (
        f"Professional close-up photograph of {name.lower()} service in progress, {scene}, "
        "natural soft lighting, shallow depth of field, photorealistic stock photo style. "
        "No text, no logos, no watermarks."
    )


def cover_prompt(service: dict) -> str:
    name = service["name"]
    scene = scene_for(service)
    return (
        f"Wide landscape professional photograph showing {name.lower()} service, {scene}, "
        "natural daylight, photorealistic home service photography. "
        "No text, no logos, no watermarks."
    )


def pending_services() -> list[dict]:
    data = json.loads(MANIFEST.read_text())
    out = []
    for svc in data["services"]:
        slug = svc["slug"]
        if (ASSETS / f"{slug}-thumbnail.png").is_file() and (ASSETS / f"{slug}-cover.png").is_file():
            continue
        out.append(
            {
                "slug": slug,
                "name": svc["name"],
                "category_slug": svc.get("category_slug", ""),
                "thumbnail_prompt": thumb_prompt(svc),
                "cover_prompt": cover_prompt(svc),
                "thumbnail_path": str(ASSETS / f"{slug}-thumbnail.png"),
                "cover_path": str(ASSETS / f"{slug}-cover.png"),
            }
        )
    return out


def main() -> None:
    pending = pending_services()
    out_path = Path(__file__).resolve().parent / "data" / "missing-catalog-photo-prompts.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(pending, indent=2))
    print(f"Wrote {len(pending)} pending prompts to {out_path}")


if __name__ == "__main__":
    main()
