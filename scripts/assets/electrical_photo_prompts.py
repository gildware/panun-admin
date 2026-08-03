#!/usr/bin/env python3
"""Build photorealistic image prompts for electrical services (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "electrical-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"
OUT = Path(__file__).resolve().parent / "data" / "electrical-photo-prompts.json"

SCENE = {
    "electric-installation": "a clean modern Indian home interior in Srinagar Kashmir",
    "electric-repair": "a lived-in Indian home interior in Srinagar Kashmir",
    "electric-inspection": "a residential switchboard area in a Srinagar Kashmir home",
}

ACTION = {
    "electric-light-install": "installing a ceiling light fixture with electrician tools",
    "electric-fan-install": "installing a ceiling fan with secure mounting and wiring",
    "electric-switch-install": "fitting a wall switchboard and sockets carefully",
    "electric-wiring-install": "routing and connecting house electrical wiring safely",
    "electric-full-house-wiring": "inspecting and planning full-house wiring at a distribution board",
    "electric-point-install": "installing a dedicated AC or geyser power point",
    "electric-mcb-install": "installing an MCB in a home distribution board",
    "electric-earthing-install": "installing home earthing connection with testing tools",
    "electric-accessory-install": "installing a stabilizer or doorbell accessory",
    "electric-inverter-install": "connecting a home inverter UPS with wiring at the DB",
    "electric-solar-inverter-install": "inspecting solar inverter wiring readiness at a home panel",
    "electric-temporary-wiring": "setting up temporary event electrical wiring safely",
    "electric-light-repair": "repairing a faulty ceiling light or tube light",
    "electric-fan-repair": "repairing a ceiling fan that is not spinning correctly",
    "electric-switch-repair": "repairing a sparking switch or socket on a wall board",
    "electric-mcb-repair": "diagnosing and repairing a tripping MCB in a DB panel",
    "electric-wiring-repair": "repairing damaged electrical wiring in a home wall area",
    "electric-power-repair": "diagnosing short circuit or voltage issues at a switchboard",
    "electric-db-panel-repair": "repairing an overheating home distribution board panel",
    "electric-earthing-repair": "testing and fixing home earthing continuity",
    "electric-site-check": "inspecting an unknown electrical fault with a tester",
    "electric-safety-check": "performing a home electrical safety inspection at the DB",
    "electric-pre-work-check": "surveying house wiring before renovation with checklist",
}

PRO_LINE = (
    "Kashmiri male professional in a navy blue work polo uniform with a small white embroidered "
    "Panun Kaergar logo on the chest matching the reference logo, only men, natural soft lighting, "
    "photorealistic stock photo style. No floating watermarks, no text overlays, no extra logos in the scene."
)


def thumb_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Indian home interior in Srinagar Kashmir")
    action = ACTION.get(slug, f"performing {name.lower()}")
    return (
        f"Professional close-up photograph of {name.lower()} in progress, {action}, {scene}, "
        f"{PRO_LINE} Shallow depth of field."
    )


def cover_prompt(slug: str, name: str, sub_slug: str) -> str:
    scene = SCENE.get(sub_slug, "a modern Indian home interior in Srinagar Kashmir")
    action = ACTION.get(slug, f"performing {name.lower()}")
    return (
        f"Wide landscape professional photograph showing {name.lower()}, {action}, {scene}, "
        f"{PRO_LINE} Natural daylight, home service photography composition."
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
    rows = []
    for svc in load_services():
        slug = svc["slug"]
        name = svc["name"]
        sub = svc["sub_category_slug"]
        rows.append(
            {
                "slug": slug,
                "name": name,
                "sub_category_slug": sub,
                "reference_image": LOGO,
                "thumbnail_prompt": thumb_prompt(slug, name, sub),
                "cover_prompt": cover_prompt(slug, name, sub),
                "thumbnail_path": str(ASSETS / f"{slug}-thumbnail.png"),
                "cover_path": str(ASSETS / f"{slug}-cover.png"),
            }
        )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows)} electrical photo prompts to {OUT}")


if __name__ == "__main__":
    main()
