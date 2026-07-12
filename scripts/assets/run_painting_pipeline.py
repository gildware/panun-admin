#!/usr/bin/env python3
"""Prepare painting catalog media — photoreal service images + ref-style variant icons."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent


def run(name: str) -> None:
    path = ROOT / name
    print(f"\n==> {name}")
    subprocess.run([sys.executable, str(path)], check=True)


def main() -> None:
    run("painting_photo_prompts.py")
    run("prepare_painting_assets.py")
    run("generate_painting_variant_icons.py")
    print("\nPainting pipeline done.")


if __name__ == "__main__":
    main()
