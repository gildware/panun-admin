#!/usr/bin/env python3
"""Prepare Aluminium & Steel assets from AI-generated sources (pest-control pipeline)."""

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
    run(ROOT / "aluminium_steel_photo_prompts.py", check=True)
    run(ROOT / "aluminium_steel_icon_prompts.py", check=True)
    run(ROOT / "prepare_aluminium_steel_assets.py", check=True)
    run(ROOT.parent / "prepare_aluminium_steel_ai_icons.py", check=True)
    run(
        [
            sys.executable,
            str(ROOT / "category-icons" / "make_theme_pairs.py"),
            "aluminium-steel-works",
            "metal-works-installation",
            "metal-works-repairs",
            "metal-works-fabrication",
        ],
        check=True,
    )
    print("\nAll Aluminium & Steel Works assets ready for upload.")


if __name__ == "__main__":
    main()
