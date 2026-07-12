#!/usr/bin/env python3
"""Create 16:9 cover images from square thumbnails when cover is missing."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageFilter

SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
COVER_SIZE = (1536, 1024)


def make_cover(thumb_path: Path, cover_path: Path) -> None:
    thumb = Image.open(thumb_path).convert("RGB")
    thumb = thumb.resize((1024, 1024), Image.Resampling.LANCZOS)
    bg = thumb.resize(COVER_SIZE, Image.Resampling.LANCZOS).filter(ImageFilter.GaussianBlur(18))
    fg_h = 900
    fg_w = 900
    fg = thumb.resize((fg_w, fg_h), Image.Resampling.LANCZOS)
    x = (COVER_SIZE[0] - fg_w) // 2
    y = (COVER_SIZE[1] - fg_h) // 2
    bg.paste(fg, (x, y))
    cover_path.parent.mkdir(parents=True, exist_ok=True)
    bg.save(cover_path, "PNG", optimize=True)
    print(f"Wrote {cover_path}")


def main() -> None:
    pairs = [
        "womens-makeup",
        "womens-manicure",
        "womens-pedicure",
        "hair-cut-styling",
        "womens-hair-coloring",
        "womens-hair-spa",
        "womens-face-cleanup",
    ]
    for slug in pairs:
        thumb = SRC / f"{slug}-thumbnail.png"
        cover = SRC / f"{slug}-cover.png"
        if thumb.is_file() and not cover.is_file():
            make_cover(thumb, cover)


if __name__ == "__main__":
    main()
