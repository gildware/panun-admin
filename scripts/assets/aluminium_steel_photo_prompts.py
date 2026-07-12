#!/usr/bin/env python3
"""Photorealistic image prompts for Aluminium & Steel Works services."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "aluminium-steel-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
OUT = Path(__file__).resolve().parent / "data" / "aluminium-steel-photo-prompts.json"

# Service-specific scenes — Kashmiri / Indian home & shop context
SERVICE_SCENES: dict[str, tuple[str, str]] = {
    "acp-cladding-installation": (
        "Indian metal fabricator fitting silver-grey ACP aluminium composite panels onto a modern shop exterior wall, drill and aluminium profile visible",
        "wide view of Indian commercial shop front with fresh ACP cladding panels being installed on facade",
    ),
    "aluminium-window-installation": (
        "close-up of aluminium sliding window frame being fitted into a modern Indian home opening, technician aligning frame with level",
        "Indian home living room with new aluminium sliding windows being installed, natural daylight",
    ),
    "aluminium-door-installation": (
        "aluminium sliding glass door track being installed at Indian home balcony entrance, professional fitter at work",
        "wide shot of aluminium folding door installation at modern Indian apartment balcony",
    ),
    "upvc-window-installation": (
        "white uPVC window frame being secured into brick opening of Indian home, silicone and screws visible",
        "Indian bedroom with new white uPVC double-glazed windows, installer finishing edges",
    ),
    "upvc-door-installation": (
        "white uPVC door frame installation at Indian home main entrance, hinges and multi-point lock hardware",
        "modern Indian flat entrance with new uPVC door being fitted",
    ),
    "balcony-railing-installation": (
        "stainless steel balcony railing posts being welded and fixed on Indian apartment balcony edge",
        "Indian high-rise apartment balcony with new SS glass railing installation overlooking city",
    ),
    "staircase-railing-installation": (
        "metal staircase handrail being measured and fixed along indoor Indian home staircase",
        "elegant stainless steel staircase railing installation in modern Indian duplex home",
    ),
    "pvc-wall-panelling-installation": (
        "white PVC wall panels being clicked into aluminium profile on Indian living room wall",
        "Indian bedroom interior with glossy PVC wall panelling installation in progress",
    ),
    "false-ceiling-installation": (
        "aluminium T-grid false ceiling being assembled in Indian office room, gypsum boards nearby",
        "Indian office ceiling with white false ceiling panels and recessed LED cutouts being installed",
    ),
    "ms-gate-installation": (
        "mild steel main gate being hung on hinges at Indian home compound wall, welder aligning frame",
        "Indian residential driveway with new black MS sliding gate installation",
    ),
    "ss-grill-installation": (
        "stainless steel window grill being bolted onto Indian home window frame for security",
        "Indian home exterior windows with shiny new SS safety grills being fitted",
    ),
    "glass-partition-installation": (
        "aluminium-framed glass office partition being installed between desks in Indian office",
        "modern Indian office with transparent glass partition walls and aluminium frames",
    ),
    "shop-shutter-installation": (
        "rolling metal shop shutter being mounted above Indian retail storefront",
        "Indian market shop front with new grey rolling shutter partially lowered",
    ),
    "pergola-car-porch-installation": (
        "aluminium pergola structure being assembled over Indian home car porch parking area",
        "modern Indian villa car porch with new metal pergola roof structure",
    ),
    "signage-frame-installation": (
        "aluminium signage frame being mounted above Indian shop board, electrician helper steadying ladder",
        "Indian street shop with new illuminated signage aluminium frame installation",
    ),
    "acp-panel-repair": (
        "technician replacing damaged ACP cladding panel on Indian shop exterior, removing bent sheet",
        "Indian building facade with worker repairing loose ACP panel section",
    ),
    "aluminium-window-repair": (
        "repair technician fixing aluminium window roller and track in Indian home, tools on sill",
        "Indian apartment with aluminium window being adjusted for smooth sliding movement",
    ),
    "aluminium-door-repair": (
        "aluminium door lock and handle being repaired on Indian balcony door, alignment check",
        "technician adjusting aluminium sliding door wheels at Indian home entrance",
    ),
    "upvc-window-door-repair": (
        "uPVC window hinge and gasket being replaced in Indian home, sealant gun nearby",
        "Indian home uPVC door hardware repair with technician tightening multi-point lock",
    ),
    "railing-repair": (
        "welder repairing loose stainless steel balcony railing joint on Indian apartment",
        "Indian staircase metal handrail section being re-welded and secured",
    ),
    "gate-grill-repair": (
        "MS gate hinge being welded and repainted at Indian home compound entrance",
        "Indian window SS grill bar being re-welded where broken",
    ),
    "false-ceiling-repair": (
        "technician replacing sagging gypsum false ceiling tile in Indian office, T-section visible",
        "Indian room with worker fixing dropped false ceiling panel back into aluminium grid",
    ),
    "pvc-panel-repair": (
        "loose PVC wall panel being refixed on Indian interior wall, clips and adhesive",
        "Indian living room with technician repairing cracked PVC wall panel section",
    ),
    "shop-shutter-repair": (
        "Indian shop rolling shutter slat and spring being repaired, technician on ladder",
        "technician fixing jammed metal shop shutter track at Indian storefront",
    ),
    "custom-ms-gate-fabrication": (
        "Indian welder fabricating decorative mild steel gate in small workshop, sparks flying",
        "custom MS gate design being measured and cut in metal fabrication workshop India",
    ),
    "custom-ss-grill-fabrication": (
        "stainless steel window grill pattern being welded in Indian metal workshop",
        "Indian fabricator crafting custom SS security grill with geometric design",
    ),
    "custom-railing-fabrication": (
        "custom stainless steel balcony railing being fabricated on workshop bench India",
        "Indian metal worker welding custom staircase railing sections in fabrication shop",
    ),
    "custom-aluminium-window-fabrication": (
        "aluminium window profiles being cut and assembled in Indian aluminium workshop",
        "Indian fabricator building custom aluminium window frame on work table",
    ),
    "steel-bracket-fabrication": (
        "steel L-brackets and support frames being cut and welded in Indian workshop",
        "Indian metal fabricator making custom steel wall brackets with angle grinder",
    ),
}

SUB_SCENE = {
    "metal-works-installation": "modern Indian home or shop installation site",
    "metal-works-repairs": "Indian home or commercial space needing metal repair",
    "metal-works-fabrication": "Indian metal fabrication workshop",
}


def thumb_prompt(name: str, slug: str, sub_slug: str) -> str:
    scene = SERVICE_SCENES.get(slug, (f"{name.lower()} work", f"{name.lower()} service"))[0]
    return (
        f"Professional close-up photograph of {scene}, "
        f"{SUB_SCENE.get(sub_slug, 'modern Indian building')}, "
        "Kashmiri Indian metal works technician in clean work clothes, natural soft lighting, "
        "shallow depth of field, photorealistic stock photo style. No text, no logos, no watermarks."
    )


def cover_prompt(name: str, slug: str, sub_slug: str) -> str:
    scene = SERVICE_SCENES.get(slug, (f"{name.lower()} work", f"{name.lower()} service"))[1]
    return (
        f"Wide landscape professional photograph showing {scene}, "
        f"{SUB_SCENE.get(sub_slug, 'modern Indian building')}, "
        "natural daylight, photorealistic home service photography. No text, no logos, no watermarks."
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
    ASSETS.mkdir(parents=True, exist_ok=True)
    rows = []
    for svc in load_services():
        slug = svc["slug"]
        sub = svc.get("sub_category_slug", "")
        rows.append(
            {
                "slug": slug,
                "name": svc["name"],
                "sub_category_slug": sub,
                "thumbnail_prompt": thumb_prompt(svc["name"], slug, sub),
                "cover_prompt": cover_prompt(svc["name"], slug, sub),
                "thumbnail_path": str(ASSETS / f"{slug}-thumbnail.png"),
                "cover_path": str(ASSETS / f"{slug}-cover.png"),
            }
        )
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows)} prompts to {OUT}")


if __name__ == "__main__":
    main()
