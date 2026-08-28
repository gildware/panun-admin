#!/usr/bin/env python3
"""Prepare Booster Pump assets from AI-generated sources."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent


def run(script: Path) -> None:
    print(f"\n==> {script.name}")
    result = subprocess.run([sys.executable, str(script)], check=False)
    if result.returncode != 0:
        raise SystemExit(result.returncode)


def main() -> None:
    run(ROOT / "booster_pump_icon_prompts.py")
    run(ROOT / "booster_pump_photo_prompts.py")
    run(ROOT / "prepare_booster_pump_assets.py")
    run(ROOT.parent / "prepare_booster_pump_ai_icons.py")
    print("\nAll Booster Pump assets ready for upload.")


if __name__ == "__main__":
    main()
