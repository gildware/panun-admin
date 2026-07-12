#!/usr/bin/env python3
"""
Run missing-catalog asset pipeline (Carpentry Repairs format):
  1. prepare_missing_catalog_assets.py  — resize photorealistic sources
  2. generate_missing_catalog_assets.py — ref-style variant icons
"""

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
    run("prepare_missing_catalog_assets.py")
    run("generate_missing_catalog_assets.py")
    print("\nPipeline done.")


if __name__ == "__main__":
    main()
