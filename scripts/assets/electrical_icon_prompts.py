#!/usr/bin/env python3
"""AI icon prompts for electrical categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "electrical-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "electrical-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "electrical": (
        "Category icon: lightning bolt with gear and plug, professional electrician services. " + ICON_STYLE
    ),
    "electric-installation": (
        "Category icon: wall socket with plus install mark and screwdriver, electric installation. " + ICON_STYLE
    ),
    "electric-repair": (
        "Category icon: lightning bolt with wrench and magnifying glass, electric repairs. " + ICON_STYLE
    ),
    "electric-inspection": (
        "Category icon: clipboard checklist with lightning bolt and shield, electric inspection. " + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "bulb": "light bulb silhouette",
    "tube-light": "tube light bar silhouette",
    "ceiling-light": "round ceiling light fixture silhouette",
    "hanging-light": "hanging pendant lamp silhouette",
    "chandelier": "chandelier light fixture silhouette",
    "decorative-light": "decorative wall light silhouette",
    "ceiling-fan": "ceiling fan blades silhouette",
    "exhaust-fan": "square exhaust fan grille silhouette",
    "bldc-fan": "modern BLDC ceiling fan silhouette",
    "switch": "wall switch plate silhouette",
    "socket": "3-pin wall socket silhouette",
    "fan-regulator": "fan speed regulator dial silhouette",
    "switchboard": "multi-switch switchboard silhouette",
    "ac-switchboard": "AC isolator switchboard silhouette",
    "internal-wiring": "internal house wiring cable silhouette",
    "external-wiring": "external casing wire run silhouette",
    "concealed-wiring": "concealed in-wall wiring conduit silhouette",
    "underground-wiring": "underground cable trench silhouette",
    "new-room-wiring": "room floor plan with wiring points silhouette",
    "book-inspection": "clipboard with checklist and magnifying glass site inspection icon",
    "geyser-point": "geyser water heater power point silhouette",
    "ac-point": "air conditioner power point silhouette",
    "exhaust-chimney-point": "kitchen chimney power point silhouette",
    "mcb": "MCB circuit breaker switch silhouette",
    "db-panel": "electrical distribution board panel silhouette",
    "new-earthing": "earthing ground rod with earth symbol silhouette",
    "stabilizer": "voltage stabilizer unit silhouette",
    "submeter": "electric submeter dial silhouette",
    "doorbell": "doorbell button silhouette",
    "inverter-ups-with-wiring": "home inverter UPS with battery wiring silhouette",
    "event-temporary-setup": "temporary event power cable board silhouette",
    "bulb-tube-ceiling-light": "bulb and tube light repair silhouette",
    "not-spinning": "ceiling fan with stop symbol silhouette",
    "slow-speed": "ceiling fan with slow speed arrows silhouette",
    "noisy": "ceiling fan with sound waves silhouette",
    "switch-socket": "switch and socket pair silhouette",
    "fuse": "electrical fuse cartridge silhouette",
    "burnt-damaged-wire": "burnt damaged electrical wire silhouette",
    "short-circuit": "short circuit spark warning silhouette",
    "tripping-voltage-issue": "voltage fluctuation gauge silhouette",
    "pcb-auto-cut": "PCB circuit board with auto-cut silhouette",
    "panel-fault-overheating": "overheating DB panel warning silhouette",
    "earthing-fix": "earthing repair ground symbol with wrench silhouette",
    "fault-check": "magnifying glass over lightning bolt fault check silhouette",
    "unknown-problem": "question mark with lightning bolt silhouette",
    "full-home-safety-check": "home shield with lightning safety check silhouette",
    "earthing-check": "earthing test probe and earth symbol silhouette",
    "mcb-db-panel-check": "DB panel inspection checklist silhouette",
    "voltage-load-check": "voltage meter load check silhouette",
    "short-circuit-risk-check": "short circuit risk warning shield silhouette",
    "before-renovation-wiring-check": "renovation house wiring survey clipboard silhouette",
    "full-house-wiring-survey": "full house wiring floor plan survey silhouette",
}


def load_catalog() -> dict:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode(require "{CATALOG}");'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def variant_prompt(service_name: str, variant_key: str, variant_title: str) -> str:
    subject = VARIANT_SUBJECTS.get(variant_key, f"{variant_title} electric option icon")
    return f"Variation icon for {service_name.lower()}: {subject}. {ICON_STYLE}"


def main() -> None:
    catalog = load_catalog()
    categories = []
    for slug, prompt in CATEGORY_PROMPTS.items():
        categories.append({"slug": slug, "filename": f"{slug}.png", "prompt": prompt})

    variants = []
    for svc in catalog["services"]:
        for var in svc["variants"]:
            key = var["variant_key"]
            filename = f"{svc['slug']}-{key}.png"
            variants.append(
                {
                    "service_slug": svc["slug"],
                    "service_name": svc["name"],
                    "variant_key": key,
                    "variant_title": var["title"],
                    "filename": filename,
                    "prompt": variant_prompt(svc["name"], key, var["title"]),
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"categories": categories, "variants": variants}, indent=2))
    print(f"Wrote {len(categories)} category + {len(variants)} variant prompts to {OUT}")


if __name__ == "__main__":
    main()
