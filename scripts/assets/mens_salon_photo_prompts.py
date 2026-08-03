#!/usr/bin/env python3
"""Build photorealistic image prompts for Men's Salon (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "mens-salon-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"
OUT = Path(__file__).resolve().parent / "data" / "mens-salon-photo-prompts.json"

SCENE = {
    "mens-hair-services": "a clean modern Srinagar Kashmir home living room grooming setup",
    "mens-beard-shaving": "a clean modern Srinagar Kashmir home living room with grooming chair setup",
    "mens-skin-grooming-care": "a clean modern Srinagar Kashmir home living room spa grooming setup",
}

ACTION = {
    "mens-hair-cut": "giving a neat men's haircut with clippers and comb to a male client",
    "mens-kids-hair-cut": "giving a careful boys haircut with scissors to a young male client",
    "mens-hair-color": "applying men's hair color carefully to a male client's hair",
    "mens-hair-treatment": "applying hair treatment to a male client's scalp and hair",
    "mens-beard-trimming": "trimming and shaping a male client's beard with a trimmer",
    "mens-clean-shave": "performing a clean shave for a male client with razor and foam",
    "mens-beard-color": "applying beard color carefully to a male client's beard",
    "mens-detan": "applying face detan cream carefully to a male client's face",
    "mens-waxing": "performing men's chest or body waxing carefully on a male client",
    "mens-facial-cleanup": "doing a men's facial cleanup on a male client's face",
    "mens-threading": "threading a male client's eyebrows carefully",
    "mens-pedicure": "performing a men's express pedicure on a male client's feet",
    "mens-manicure": "performing a men's express manicure on a male client's hands",
    "mens-nail-cut-file": "cutting and filing a male client's nails carefully",
    "mens-massage": "giving a head and neck massage to a male client",
}

PRO_LINE = (
    "Kashmiri male professional in a navy blue work polo uniform with a small white embroidered "
    "Panun Kaergar logo on the chest matching the reference logo, only men, natural soft lighting, "
    "photorealistic stock photo style. No floating watermarks, no text overlays, no extra logos in the scene."
)


def thumb_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Srinagar Kashmir home interior")
    action = ACTION.get(slug, f"performing {name.lower()} for a male client")
    return (
        f"Professional close-up photograph of {name.lower()} in progress, {action}, {scene}, "
        f"{PRO_LINE} Shallow depth of field. Male client only."
    )


def cover_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Srinagar Kashmir home interior")
    action = ACTION.get(slug, f"performing {name.lower()} for a male client")
    return (
        f"Wide landscape professional photograph showing {name.lower()}, {action}, {scene}, "
        f"{PRO_LINE} Natural daylight, home service photography composition. Male client only."
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
    print(f"Wrote {len(rows)} mens-salon photo prompts to {OUT}")


if __name__ == "__main__":
    main()
