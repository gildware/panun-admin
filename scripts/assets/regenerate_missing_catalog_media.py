#!/usr/bin/env python3
"""Regenerate all missing-catalog media in PK Carpentry Repairs format."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent


def run(script: str) -> None:
    path = ROOT / script
    print(f"\n==> {script}")
    subprocess.run([sys.executable, str(path)], check=True)


def main() -> None:
    run("prepare_missing_catalog_assets.py")
    run("generate_missing_catalog_assets.py")
    print("\nDone — photorealistic service images + ref-style variant icons.")


if __name__ == "__main__":
    main()
