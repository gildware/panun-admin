#!/usr/bin/env python3
"""Copy photorealistic pest control service images from closest PK photo donors."""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

from PIL import Image

SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
SCRIPTS = Path(__file__).resolve().parents[1]
CATALOG = SCRIPTS / "data" / "pest-control-catalog.php"
SERVICE_IMG = Path(__file__).resolve().parent / "service-images"

# Best photoreal donors for pest-control services when custom photos are unavailable.
SERVICE_DONORS = {
    "apartment-cockroach-control": "full-home-cleaning",
    "bungalow-cockroach-control": "full-home-cleaning",
    "kitchen-cockroach-control": "kitchen-cleaning",
    "partial-home-cockroach-control": "full-home-cleaning",
    "office-cockroach-control": "office-ready-cleanup",
    "office-rodent-control": "office-ready-cleanup",
    "office-ant-control": "office-ready-cleanup",
    "restaurant-kitchen-pest-control": "kitchen-cleaning",
    "restaurant-dining-pest-control": "office-ready-cleanup",
    "restaurant-cockroach-control": "kitchen-cleaning",
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


def copy_resized(src: Path, dst: Path, size: tuple[int, int]) -> None:
    img = Image.open(src).convert("RGB").resize(size, Image.Resampling.LANCZOS)
    dst.parent.mkdir(parents=True, exist_ok=True)
    img.save(dst, "PNG", optimize=True)


def load_services() -> list[dict]:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode((require "{CATALOG}")["services"]);'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def pick_donor(slug: str, sub_category: str) -> str:
    if slug in SERVICE_DONORS:
        donor = SERVICE_DONORS[slug]
        if (SERVICE_IMG / donor / "thumbnail.png").is_file():
            return donor
    defaults = {
        "home-pest-control": "full-home-cleaning",
        "office-pest-control": "office-ready-cleanup",
        "restaurant-pest-control": "kitchen-cleaning",
    }
    return defaults.get(sub_category, "full-home-cleaning")


def main() -> None:
    services = load_services()
    for svc in services:
        slug = svc["slug"]
        if (SRC / f"{slug}-thumbnail.png").is_file() and (SRC / f"{slug}-cover.png").is_file():
            print(f"SKIP {slug}: custom sources in assets/")
            continue
        donor = pick_donor(slug, svc.get("sub_category_slug", ""))
        donor_dir = SERVICE_IMG / donor
        thumb = donor_dir / "thumbnail.png"
        cover = donor_dir / "cover.png"
        if not thumb.is_file() or not cover.is_file() or not is_photo(thumb):
            print(f"SKIP {slug}: no photo donor at {donor}")
            continue
        out_dir = SERVICE_IMG / slug
        copy_resized(thumb, out_dir / "thumbnail.png", (1024, 1024))
        copy_resized(cover, out_dir / "cover.png", (1536, 1024))
        print(f"{slug} <- {donor} (1024x1024 / 1536x1024)")


if __name__ == "__main__":
    main()
