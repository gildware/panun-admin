#!/usr/bin/env python3
"""Build photorealistic image prompts for Pet Grooming (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "pet-grooming-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"
OUT = Path(__file__).resolve().parent / "data" / "pet-grooming-photo-prompts.json"

SCENE = {
    "dog-grooming": "a clean modern Srinagar Kashmir home living room or bathroom, at-home dog grooming visit",
    "cat-grooming": "a clean modern Srinagar Kashmir home living room or bathroom, at-home cat grooming visit",
}

ACTION = {
    "full-dog-grooming": "giving full dog grooming with bath, brush, and coat trim to a calm dog",
    "dog-bath-and-brush": "bathing and blow-drying a dog with brush in hand",
    "dog-haircut-and-trim": "trimming a dog coat with scissors and clippers",
    "dog-spa-package": "giving a spa bath and gentle massage to a relaxed dog",
    "dog-flea-tick-bath": "giving a medicated flea and tick bath to a dog",
    "dog-deshedding-treatment": "deshedding a dog with an undercoat rake brush",
    "dog-mat-removal": "carefully dematting a dog coat with comb and clippers",
    "puppy-first-groom": "gently introducing a puppy to first grooming with patient handling",
    "senior-dog-gentle-groom": "gently grooming a senior dog on a soft mat at a slow pace",
    "dog-nail-clipping": "carefully clipping a dog nails with pet nail clippers",
    "dog-ear-cleaning": "gently cleaning a dog ears with cotton pad",
    "dog-paw-pad-trim": "trimming fur around a dog paw pads",
    "dog-teeth-brushing": "brushing a dog teeth with pet toothbrush",
    "full-cat-grooming": "giving full cat grooming with bath, brush, and tidy trim to a calm cat",
    "cat-bath-and-brush": "bathing and brushing a cat carefully",
    "cat-spa-package": "giving a spa bath and gentle massage to a relaxed cat",
    "cat-flea-tick-bath": "giving a medicated flea and tick bath to a cat",
    "cat-lion-cut": "giving a lion cut trim to a cat on a grooming mat",
    "cat-mat-removal": "carefully dematting a long-haired cat coat",
    "kitten-first-groom": "gently introducing a kitten to first grooming with patient handling",
    "senior-cat-gentle-groom": "gently grooming a senior cat on a soft mat at a slow pace",
    "cat-nail-trim": "carefully trimming a cat nails with pet nail clippers",
    "cat-ear-cleaning": "gently cleaning a cat ears with cotton pad",
    "cat-teeth-brushing": "brushing a cat teeth with pet toothbrush",
}

PRO_LINE = (
    "Kashmiri male professional in a navy blue work polo uniform with a small white embroidered "
    "Panun Kaergar logo on the chest matching the reference logo, only men, natural soft lighting, "
    "photorealistic stock photo style. No floating watermarks, no text overlays, no extra logos in the scene."
)


def thumb_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Srinagar Kashmir home interior")
    action = ACTION.get(slug, f"performing {name.lower()}")
    pet = "cat" if "cat" in sub_slug else "dog"
    return (
        f"Professional close-up photograph of {name.lower()} in progress, {action}, {scene}, "
        f"{PRO_LINE} Shallow depth of field. Focus on groomer and {pet}."
    )


def cover_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Srinagar Kashmir home interior")
    action = ACTION.get(slug, f"performing {name.lower()}")
    pet = "cat" if "cat" in sub_slug else "dog"
    return (
        f"Wide landscape professional photograph showing {name.lower()}, {action}, {scene}, "
        f"{PRO_LINE} Natural daylight, home service photography composition. Focus on groomer and {pet}."
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
    OUT.write_text(json.dumps(rows, indent=2) + "\n")
    print(f"Wrote {len(rows)} pet-grooming photo prompts to {OUT}")


if __name__ == "__main__":
    main()
