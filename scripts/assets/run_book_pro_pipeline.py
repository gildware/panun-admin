#!/usr/bin/env python3
"""Prepare Book Painter / Mason / Carpenter assets and variant icons."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
SLUGS = ["book-a-carpenter", "book-a-painter", "book-a-mason"]


def run(name: str) -> None:
    path = ROOT / name
    print(f"\n==> {name}")
    subprocess.run([sys.executable, str(path)], check=True)


def prepare_selected() -> None:
    from PIL import Image

    service_img = ROOT / "service-images"
    for slug in SLUGS:
        thumb_src = ASSETS / f"{slug}-thumbnail.png"
        cover_src = ASSETS / f"{slug}-cover.png"
        if not thumb_src.is_file() or not cover_src.is_file():
            raise SystemExit(f"Missing photorealistic source for {slug} in {ASSETS}")

        out_dir = service_img / slug
        out_dir.mkdir(parents=True, exist_ok=True)

        thumb = Image.open(thumb_src).convert("RGB").resize((1024, 1024), Image.Resampling.LANCZOS)
        cover = Image.open(cover_src).convert("RGB").resize((1536, 1024), Image.Resampling.LANCZOS)
        thumb.save(out_dir / "thumbnail.png", "PNG", optimize=True)
        cover.save(out_dir / "cover.png", "PNG", optimize=True)
        print(f"Prepared {out_dir}")


def main() -> None:
    prepare_selected()
    run("generate_book_pro_variant_icons.py")
    print("\nBook-pro pipeline done.")


if __name__ == "__main__":
    main()
