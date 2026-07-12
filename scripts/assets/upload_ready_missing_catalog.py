#!/usr/bin/env python3
"""
Prepare assets + refresh live for services that have photorealistic sources ready.
Skips services missing thumb/cover in the assets folder.
"""

from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
MANIFEST = ROOT / "scripts/data/missing-catalog-manifest.json"


def ready_slugs() -> list[str]:
    data = json.loads(MANIFEST.read_text())
    ready = []
    for svc in data["services"]:
        slug = svc["slug"]
        if (ASSETS / f"{slug}-thumbnail.png").is_file() and (ASSETS / f"{slug}-cover.png").is_file():
            ready.append(slug)
    return ready


def main() -> None:
    ready = ready_slugs()
    print(f"Ready for upload: {len(ready)}/286")
    if not ready:
        return

    subprocess.run(
        [sys.executable, str(ROOT / "scripts/assets/run_missing_catalog_pipeline.py")],
        check=True,
        cwd=ROOT,
    )

    password = __import__("os").environ.get("LIVE_DB_PASSWORD", "")
    if not password:
        print("Set LIVE_DB_PASSWORD to upload to live.")
        return

    env = {**__import__("os").environ, "IMPORT_REFRESH_EXISTING": "1"}
    for slug in ready:
        print(f"\n--- Import {slug} ---")
        subprocess.run(
            [
                "php",
                "artisan",
                "tinker",
                "scripts/import-missing-catalog-live.php",
            ],
            check=False,
            cwd=ROOT,
            env={**env, "IMPORT_SLUG": slug, "LIVE_DB_PASSWORD": password},
        )


if __name__ == "__main__":
    main()
