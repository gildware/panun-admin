#!/usr/bin/env python3
"""Generate all pest control assets — AI icons + photoreal service images (laundry/cleaning standard)."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent


def run(script: Path | list, check: bool = True) -> int:
    if isinstance(script, Path):
        cmd = [sys.executable, str(script)]
        label = script.name
    else:
        cmd = script
        label = Path(cmd[1]).name
    print(f"\n==> {label}")
    result = subprocess.run(cmd, check=False)
    if check and result.returncode != 0:
        raise SystemExit(result.returncode)
    return result.returncode


def main() -> None:
    # 1. Photoreal service images (1024 thumb / 1536x1024 cover)
    run(ROOT / "prepare_pest_control_assets.py", check=True)
    # 2. AI category + variant icons -> scripts/assets (requires icons in Cursor assets/)
    run(ROOT.parent / "prepare_pest_control_ai_icons.py", check=True)
    # 3. Light/dark category theme pairs
    run(
        [
            sys.executable,
            str(ROOT / "category-icons" / "make_theme_pairs.py"),
            "pest-control",
            "home-pest-control",
            "office-pest-control",
            "restaurant-pest-control",
        ],
        check=True,
    )
    print("\nAll pest control assets ready for upload.")


if __name__ == "__main__":
    main()
