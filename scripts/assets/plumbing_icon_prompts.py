#!/usr/bin/env python3
"""AI icon prompts for plumbing categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "plumbing-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "plumbing-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "plumbing": (
        "Category icon: water drop with wrench and pipe, professional plumbing services. " + ICON_STYLE
    ),
    "plumbing-installation": (
        "Category icon: tap with plus install mark and pipe wrench, plumbing installation. " + ICON_STYLE
    ),
    "plumbing-repair": (
        "Category icon: leaking pipe with wrench and water drop, plumbing repairs. " + ICON_STYLE
    ),
    "plumbing-inspection": (
        "Category icon: clipboard checklist with water drop and magnifying glass, plumbing inspection. " + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "regular-tap": "regular water tap faucet silhouette",
    "mixer-tap": "hot cold mixer tap silhouette",
    "swan-neck-tap": "swan neck kitchen tap silhouette",
    "pillar-cock": "pillar cock bathroom tap silhouette",
    "angle-valve": "angle valve plumbing fitting silhouette",
    "shower-head": "round shower head silhouette",
    "hand-shower-jet-spray": "hand shower jet spray silhouette",
    "shower-mixer": "shower mixer control silhouette",
    "wash-basin": "wash basin sink silhouette",
    "pedestal-basin": "pedestal wash basin silhouette",
    "bottle-trap": "bottle trap under sink pipe silhouette",
    "indian-toilet": "Indian toilet floor squat silhouette",
    "western-toilet-floor": "western floor mounted toilet silhouette",
    "western-toilet-wall": "western wall hung toilet silhouette",
    "flush-tank-external": "external toilet flush tank silhouette",
    "flush-tank-concealed": "concealed flush tank wall plate silhouette",
    "kitchen-sink": "kitchen sink basin silhouette",
    "connection-hose": "flexible plumbing connection hose silhouette",
    "pvc-cpvc-pipe": "PVC CPVC pipe section silhouette",
    "gi-metal-pipe": "GI metal pipe with threads silhouette",
    "concealed-pipe": "concealed in-wall plumbing pipe silhouette",
    "external-pipe": "external wall plumbing pipe run silhouette",
    "floor-drain-nahani-trap": "floor drain nahani trap grille silhouette",
    "drain-cover": "round drain cover grille silhouette",
    "waste-pipe": "waste drain pipe elbow silhouette",
    "water-motor-pump": "home water motor pump silhouette",
    "motor-with-piping": "water pump with connected piping silhouette",
    "overhead-tank-connection": "overhead water tank with pipe connection silhouette",
    "float-valve-ball-cock": "float valve ball cock silhouette",
    "tank-cover-fit": "water tank cover lid silhouette",
    "hot-cold-water-connection": "geyser hot cold water pipe connection silhouette",
    "shut-off-valve": "shut-off valve plumbing silhouette",
    "non-return-valve": "non-return check valve silhouette",
    "pressure-pump-connection": "pressure booster pump connection silhouette",
    "book-inspection": "clipboard with checklist and magnifying glass site inspection icon",
    "renovation-new-setup": "bathroom kitchen renovation plumbing blueprint silhouette",
    "leaking-dripping": "dripping leaking tap silhouette",
    "shower-head-arm": "shower head with arm pipe silhouette",
    "jet-spray-bidet": "jet spray bidet hand shower silhouette",
    "leakage-bottle-trap": "leaking bottle trap under basin silhouette",
    "leakage-waste-pipe": "leaking waste pipe silhouette",
    "blockage": "blocked drain with clog symbol silhouette",
    "flush-tank-external-pvc": "external PVC flush tank silhouette",
    "flush-tank-external-ceramic": "external ceramic flush tank silhouette",
    "running-flush-weak-flush": "toilet flush with weak flow arrows silhouette",
    "seat-cisterna-fix": "toilet seat and cistern fix silhouette",
    "kitchen-sink-blockage": "blocked kitchen sink silhouette",
    "wash-basin-blockage": "blocked wash basin silhouette",
    "bathroom-floor-drain": "bathroom floor drain blockage silhouette",
    "toilet-pot-blockage": "blocked toilet pot silhouette",
    "bad-smell-trap-issue": "drain smell wavy lines trap silhouette",
    "leak-joint-fix": "pipe joint leak with wrench silhouette",
    "burst-damaged-pipe": "burst damaged water pipe silhouette",
    "concealed-pipe-leak": "concealed wall pipe leak silhouette",
    "external-pipe-leak": "external pipe leak water drop silhouette",
    "visible-leak": "visible water leak drip silhouette",
    "hidden-wall-seepage": "wall damp seepage stain silhouette",
    "shut-off-valve-leak": "leaking shut-off valve silhouette",
    "low-water-pressure": "low water pressure gauge silhouette",
    "no-water-airlock": "empty tap with airlock bubble silhouette",
    "not-starting": "water motor with stop symbol silhouette",
    "low-pressure-weak-flow": "weak water flow from pump silhouette",
    "noise-overheating": "motor with heat and sound waves silhouette",
    "air-cavity-removal": "pump air cavity bleed valve silhouette",
    "overflow-float-valve": "tank overflow float valve silhouette",
    "connection-leakage": "tank pipe connection leak silhouette",
    "cover-change": "water tank cover replacement silhouette",
    "inlet-outlet-leak": "geyser inlet outlet leak silhouette",
    "leak-check": "magnifying glass over water leak check silhouette",
    "blockage-check": "magnifying glass over blocked drain check silhouette",
    "unknown-problem": "question mark with water drop silhouette",
    "full-home-plumbing-check": "home shield with water drop safety check silhouette",
    "pipe-joint-check": "pipe joint inspection checklist silhouette",
    "motor-tank-check": "motor and overhead tank inspection silhouette",
    "drain-smell-backflow-check": "drain backflow smell warning silhouette",
    "winter-freeze-risk-check": "frozen pipe winter risk warning silhouette",
    "before-renovation-plumbing-check": "renovation house plumbing survey clipboard silhouette",
    "full-house-plumbing-survey": "full house plumbing floor plan survey silhouette",
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
    subject = VARIANT_SUBJECTS.get(variant_key, f"{variant_title} plumbing option icon")
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
