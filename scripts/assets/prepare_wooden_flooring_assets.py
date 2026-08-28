#!/usr/bin/env python3
"""Copy photorealistic wooden flooring service images from Cursor assets folder."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
SERVICE_IMG = ROOT / "service-images"
SLUGS = ["wooden-flooring-install", "wooden-flooring-repair"]


def copy_service_image(slug: str, kind: str) -> None:
    src = SRC / f"{slug}-{kind}.png"
    if not src.is_file():
        raise SystemExit(f"Missing source image: {src}")
    out_dir = SERVICE_IMG / slug
    out_dir.mkdir(parents=True, exist_ok=True)
    img = Image.open(src).convert("RGB")
    if kind == "thumbnail":
        img = img.resize((1024, 1024), Image.Resampling.LANCZOS)
    else:
        img = img.resize((1536, 1024), Image.Resampling.LANCZOS)
    out = out_dir / f"{kind}.png"
    img.save(out, "PNG", optimize=True)
    print(f"Wrote {out} ({img.size[0]}x{img.size[1]})")


def main() -> None:
    for slug in SLUGS:
        copy_service_image(slug, "thumbnail")
        copy_service_image(slug, "cover")
    print(f"\nPrepared {len(SLUGS)} wooden flooring services.")


if __name__ == "__main__":
    main()
