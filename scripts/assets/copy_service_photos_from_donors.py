#!/usr/bin/env python3
"""
Copy photorealistic service thumb/cover from the closest existing PK service image
(same format as door-repair / full-home-cleaning — real photos, not gradients).

Falls back to generate_carpentry_repair_images-style gradient only when no photo donor exists.
"""

from __future__ import annotations

import json
import re
import shutil
from pathlib import Path

from PIL import Image

SCRIPTS = Path(__file__).resolve().parents[1]
MANIFEST = SCRIPTS / "data" / "missing-catalog-manifest.json"
SERVICE_IMG = Path(__file__).resolve().parent / "service-images"
REPAIR_GEN = Path(__file__).resolve().parent / "service-images" / "generate_carpentry_repair_images.py"

CATEGORY_DEFAULT = {
    "carpentary": "door-repair",
    "plumbing": "drain-pipe-blockage-removal",
    "electrical": "electrical-wiring",
    "cleaning": "full-home-cleaning",
    "home-appliance": "ac-servicing",
    "mens-salon": "mens-hair-cut",
    "womens-salon": "womens-facial-services",
    "laundry": "wash-and-iron",
}


def is_photo(path: Path) -> bool:
    try:
        img = Image.open(path).convert("RGB")
    except OSError:
        return False
    px = list(img.resize((64, 64)).getdata())
    diffs = sum(
        abs(px[i][0] - px[i + 1][0]) + abs(px[i][1] - px[i + 1][1]) + abs(px[i][2] - px[i + 1][2])
        for i in range(len(px) - 1)
    )
    return diffs / max(1, len(px) - 1) > 25


def photo_donors() -> list[str]:
    donors = []
    for d in SERVICE_IMG.iterdir():
        if not d.is_dir():
            continue
        thumb = d / "thumbnail.png"
        cover = d / "cover.png"
        if thumb.is_file() and cover.is_file() and is_photo(thumb):
            donors.append(d.name)
    return sorted(donors)


def score_donor(service_name: str, category_slug: str, donor: str) -> int:
    name = service_name.lower()
    slug = donor.lower()
    words = [w for w in re.split(r"[^a-z0-9]+", name) if len(w) > 2]
    slug_words = [w for w in slug.split("-") if len(w) > 2]
    score = sum(3 for w in words if w in slug)
    score += sum(2 for w in slug_words if w in name)
    if category_slug in slug:
        score += 2
  # category hints
    hints = {
        "door": "door",
        "window": "window",
        "furniture": "furniture",
        "wardrobe": "wardrobe",
        "kitchen": "kitchen",
        "cabinet": "cabinet",
        "roof": "roof",
        "panel": "wooden-panel",
        "sofa": "full-home-cleaning",
        "carpet": "carpet-cleaning",
        "mattress": "full-home-cleaning",
        "bathroom": "washroom-drain-cleaning",
        "toilet": "washroom-drain-cleaning",
        "tap": "tap-mixer-tap-installation",
        "geyser": "geyser-repair",
        "ac": "ac-servicing",
        "washing": "washing-machine-repair",
        "refrigerator": "refrigerator-repair",
        "fridge": "refrigerator-repair",
        "tv": "tv-installation",
        "inverter": "inverter-repair",
        "fan": "fan",
        "switch": "switch-sockets",
        "wiring": "electrical-wiring",
        "light": "lighting-installation",
        "hair": "mens-hair-cut",
        "beard": "mens-beard-trim-styling",
        "facial": "womens-facial-services",
        "wax": "womens-waxing-services",
        "manicure": "womens-manicure",
        "pedicure": "womens-pedicure",
        "makeup": "womens-makeup",
        "massage": "mens-hair-treatment",
        "clean": "full-home-cleaning",
    }
    for key, target in hints.items():
        if key in name and target in slug:
            score += 5
    return score


def pick_donor(service_name: str, category_slug: str, donors: list[str]) -> str:
    if not donors:
        return CATEGORY_DEFAULT.get(category_slug, "door-repair")
    ranked = sorted(donors, key=lambda d: score_donor(service_name, category_slug, d), reverse=True)
    best = ranked[0]
    if score_donor(service_name, category_slug, best) > 0:
        return best
    return CATEGORY_DEFAULT.get(category_slug, ranked[0])


def copy_resized(src: Path, dst: Path, size: tuple[int, int]) -> None:
    try:
        img = Image.open(src).convert("RGB").resize(size, Image.Resampling.LANCZOS)
    except OSError as e:
        raise SystemExit(f"Bad image {src}: {e}") from e
    dst.parent.mkdir(parents=True, exist_ok=True)
    img.save(dst, "PNG", optimize=True)


def main() -> None:
    data = json.loads(MANIFEST.read_text())
    donors = photo_donors()
    print(f"Photo donors available: {len(donors)}")

    used: dict[str, str] = {}
    for svc in data["services"]:
        slug = svc["slug"]
        donor = pick_donor(svc["name"], svc["category_slug"], donors)
        used[slug] = donor
        donor_dir = SERVICE_IMG / donor
        out_dir = SERVICE_IMG / slug
        copy_resized(donor_dir / "thumbnail.png", out_dir / "thumbnail.png", (1024, 1024))
        copy_resized(donor_dir / "cover.png", out_dir / "cover.png", (1536, 1024))

    print(f"Assigned photorealistic thumb/cover for {len(used)} services")
    # show sample mappings
    for slug, donor in list(used.items())[:8]:
        print(f"  {slug} <- {donor}")


if __name__ == "__main__":
    main()
