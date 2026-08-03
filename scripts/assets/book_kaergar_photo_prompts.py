#!/usr/bin/env python3
"""Build photorealistic image prompts for Book Kaergar services (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "book-kaergar-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"

SCENE = {
    "home-trades": "a clean modern Indian home interior in Srinagar Kashmir",
    "building-site": "a residential construction or renovation site in Srinagar Kashmir",
    "home-care": "a Kashmir home garden courtyard or clean living space in Srinagar",
    "beauty-artists": "a bright clean indoor event preparation space in Srinagar Kashmir",
}

ACTION = {
    "book-a-carpenter": "using carpentry tools on wooden furniture or door fittings",
    "book-an-electrician": "checking a switchboard and wiring with electrician tools",
    "book-a-plumber": "working on a sink pipe or plumbing fixture with wrench",
    "book-a-painter": "rolling paint on an interior wall with brush and tray",
    "book-a-mason": "laying bricks or tiles with trowel and spirit level",
    "book-labour": "carrying construction materials and assisting on a work site",
    "book-a-welder": "welding metal with protective gear and fabrication tools",
    "book-a-gardener": "pruning plants and tending a garden with shears and trowel",
    "book-a-cleaner": "cleaning a home floor and surfaces with professional cleaning tools",
    "book-makeup-artist": "applying professional makeup with brushes for an event client",
    "book-mehndi-artist": "applying henna mehndi design carefully with a cone",
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
    ASSETS.mkdir(parents=True, exist_ok=True)
    all_rows = []
    pending = 0
    for svc in load_services():
        slug = svc["slug"]
        thumb = ASSETS / f"{slug}-thumbnail.png"
        cover = ASSETS / f"{slug}-cover.png"
        if not thumb.is_file() or not cover.is_file():
            pending += 1
        all_rows.append(
            {
                "slug": slug,
                "name": svc["name"],
                "sub_category_slug": svc.get("sub_category_slug", ""),
                "reference_image": LOGO,
                "thumbnail_prompt": thumb_prompt(slug, svc["name"], svc.get("sub_category_slug", "")),
                "cover_prompt": cover_prompt(slug, svc["name"], svc.get("sub_category_slug", "")),
                "thumbnail_path": str(thumb),
                "cover_path": str(cover),
            }
        )
    out_path = Path(__file__).resolve().parent / "data" / "book-kaergar-photo-prompts.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(json.dumps(all_rows, indent=2))
    print(f"Wrote {len(all_rows)} photo prompts ({pending} pending) to {out_path}")


if __name__ == "__main__":
    main()
