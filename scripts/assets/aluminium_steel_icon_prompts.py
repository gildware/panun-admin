#!/usr/bin/env python3
"""AI icon prompts for Aluminium & Steel Works categories + variants."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "aluminium-steel-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "aluminium-steel-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "aluminium-steel-works": (
        "Category icon: aluminium I-beam crossed with steel angle profile and small welding spark, "
        "representing aluminium and steel fabrication works. " + ICON_STYLE
    ),
    "metal-works-installation": (
        "Category icon: aluminium window frame with wrench and screwdriver, metal installation service. "
        + ICON_STYLE
    ),
    "metal-works-repairs": (
        "Category icon: cracked metal railing with hammer and wrench, metal repair service. "
        + ICON_STYLE
    ),
    "metal-works-fabrication": (
        "Category icon: welding torch with steel gate frame silhouette, custom metal fabrication. "
        + ICON_STYLE
    ),
}

VARIANT_SUBJECT = {
    "acp": "ACP cladding panel on building facade",
    "aluminium-window": "aluminium sliding window frame",
    "aluminium-door": "aluminium sliding door",
    "upvc-window": "white uPVC window frame",
    "upvc-door": "white uPVC door",
    "balcony-railing": "stainless steel balcony railing",
    "staircase-railing": "staircase metal handrail",
    "pvc-wall": "PVC interior wall panels",
    "false-ceiling": "false ceiling T-grid panel",
    "ms-gate": "mild steel main gate",
    "ss-grill": "stainless steel window grill",
    "glass-partition": "glass office partition with aluminium frame",
    "shop-shutter": "rolling metal shop shutter",
    "pergola": "aluminium pergola car porch structure",
    "signage": "shop signage aluminium frame",
    "acp-panel": "ACP panel with crack for repair",
    "railing": "metal railing section",
    "gate-grill": "gate and window grill",
    "pvc-panel": "PVC wall panel",
    "custom-ms-gate": "custom MS gate design",
    "custom-ss-grill": "custom SS grill pattern",
    "custom-railing": "custom metal railing design",
    "custom-aluminium-window": "custom aluminium window frame",
    "steel-bracket": "steel L-bracket support",
}


def subject_for_slug(slug: str) -> str:
    for key, label in VARIANT_SUBJECT.items():
        if key in slug:
            return label
    return "metal works component"


def inspection_prompt(slug: str, name: str) -> str:
    subject = subject_for_slug(slug)
    return (
        f"Variation icon for metal works site inspection: {subject} with magnifying glass "
        f"and clipboard checklist, book on site inspection for {name}. {ICON_STYLE}"
    )


def load_services() -> list[dict]:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode((require "{CATALOG}")["services"]);'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def main() -> None:
    rows: dict = {"categories": [], "variants": []}
    for slug, prompt in CATEGORY_PROMPTS.items():
        rows["categories"].append({"slug": slug, "filename": f"{slug}.png", "prompt": prompt})

    for svc in load_services():
        slug = svc["slug"]
        name = svc["name"]
        for var in svc["variants"]:
            key = var["variant_key"]
            rows["variants"].append(
                {
                    "slug": slug,
                    "variant_key": key,
                    "filename": f"{slug}-{key}.png",
                    "prompt": inspection_prompt(slug, name),
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows['categories'])} category + {len(rows['variants'])} variant prompts -> {OUT}")


if __name__ == "__main__":
    main()
