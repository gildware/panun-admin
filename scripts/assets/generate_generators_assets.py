#!/usr/bin/env python3
"""Prepare Generators assets from AI-generated sources."""

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
        label = Path(cmd[1]).name if len(cmd) > 1 else cmd[0]
    print(f"\n==> {label}")
    result = subprocess.run(cmd, check=False)
    if check and result.returncode != 0:
        raise SystemExit(result.returncode)
    return result.returncode


def main() -> None:
    run(ROOT / "generators_icon_prompts.py", check=True)
    run(ROOT / "generators_photo_prompts.py", check=True)
    run(ROOT / "prepare_generators_assets.py", check=True)
    run(ROOT.parent / "prepare_generators_ai_icons.py", check=True)
    run(
        [
            sys.executable,
            str(ROOT / "category-icons" / "make_theme_pairs.py"),
            "generators",
        ],
        check=True,
    )
    print("\nAll Generators assets ready for upload.")


if __name__ == "__main__":
    main()
