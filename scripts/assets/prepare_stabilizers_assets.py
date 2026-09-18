#!/usr/bin/env python3
"""Prepare Stabilizers subcategory + Stabilizer Repair assets (uses macOS sips, not Pillow)."""

from __future__ import annotations

import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
CAT_SRC = ROOT / "category-icons"
VARIANT_OUT = ROOT / "variant-icons"
SERVICE_IMG = ROOT / "service-images" / "stabilizer-repair"
LIGHT = ROOT.parent / "category-icons" / "light"


def run_sips(path: Path, width: int, height: int) -> None:
    result = subprocess.run(
        ["sips", "-z", str(height), str(width), str(path)],
        check=False,
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        print(result.stdout)
        print(result.stderr, file=sys.stderr)
        raise SystemExit(result.returncode)


def copy_file(src: Path, dest: Path) -> None:
    if not src.is_file():
        raise SystemExit(f"Missing source image: {src}")
    dest.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(src, dest)
    print(f"Wrote {dest}")


def main() -> None:
    copy_file(VARIANT_OUT / "electric-accessory-install-stabilizer.png", CAT_SRC / "stabilizers.png")
    run_sips(CAT_SRC / "stabilizers.png", 512, 512)

    copy_file(
        VARIANT_OUT / "generator-repair-book-site-inspection.png",
        VARIANT_OUT / "stabilizer-repair-book-site-inspection.png",
    )
    run_sips(VARIANT_OUT / "stabilizer-repair-book-site-inspection.png", 512, 512)

    copy_file(SRC / "stabilizer-repair-thumbnail.png", SERVICE_IMG / "thumbnail.png")
    run_sips(SERVICE_IMG / "thumbnail.png", 1024, 1024)
    copy_file(SRC / "stabilizer-repair-cover.png", SERVICE_IMG / "cover.png")
    run_sips(SERVICE_IMG / "cover.png", 1536, 1024)

    copy_file(CAT_SRC / "stabilizers.png", LIGHT / "stabilizers.png")
    print("Stabilizer assets ready for upload.")


if __name__ == "__main__":
    main()
