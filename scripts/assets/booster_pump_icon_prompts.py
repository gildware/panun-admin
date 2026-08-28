#!/usr/bin/env python3
"""Build booster pump variant icon prompt list from the plumbing catalog."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "plumbing-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "booster-pump-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

VARIANT_SUBJECTS = {
    "single-line-shower": "small inline shower booster pump silhouette",
    "whole-house": "whole house water booster pump silhouette",
    "booster-with-piping": "booster pump with connected piping silhouette",
    "booster-auto-pressure-switch": "booster pump with automatic pressure switch silhouette",
    "book-site-inspection": "clipboard checklist with magnifying glass site inspection icon",
    "wont-start": "booster pump with stop symbol silhouette",
    "no-low-pressure": "booster pump with weak water pressure gauge silhouette",
    "leak": "booster pump with water leak drip silhouette",
    "noise-overheating": "booster pump with heat and sound waves silhouette",
}


def load_catalog() -> dict:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode(require "{CATALOG}");'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def main() -> None:
    catalog = load_catalog()
    variants = []
    for svc in catalog["services"]:
        if not str(svc.get("slug", "")).startswith("booster-pump-"):
            continue
        for var in svc["variants"]:
            key = var["variant_key"]
            subject = VARIANT_SUBJECTS.get(key, f"{var['title'].lower()} plumbing option icon")
            variants.append(
                {
                    "service_slug": svc["slug"],
                    "service_name": svc["name"],
                    "variant_key": key,
                    "title": var["title"],
                    "filename": f"{svc['slug']}-{key}.png",
                    "prompt": f"Variation icon for {svc['name'].lower()}: {subject}. {ICON_STYLE}",
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"variants": variants}, indent=2))
    print(f"Wrote {len(variants)} booster pump variant icon prompts to {OUT}")


if __name__ == "__main__":
    main()
