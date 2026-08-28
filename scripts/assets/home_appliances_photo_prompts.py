#!/usr/bin/env python3
"""Build photorealistic image prompts for home appliances (Kashmiri men, navy uniform, logo)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "home-appliances-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
LOGO = "/Users/kamran/Desktop/panun kaergar/Logo White.png"
OUT = Path(__file__).resolve().parent / "data" / "home-appliances-photo-prompts.json"

SCENE = {
    "air-conditioners": "a modern Srinagar Kashmir home living room or bedroom with an AC unit",
    "battery-inverters": "a Kashmir home utility corner with inverter and battery setup",
    "cctv": "a Kashmir home entrance or shop front with CCTV cameras",
    "geysers": "a clean Kashmir home bathroom with a wall geyser",
    "led-smart-tv": "a modern Kashmir living room with a wall-mounted TV",
    "refrigerators": "a Kashmir home kitchen with a refrigerator",
    "deep-freezers": "a Kashmir home kitchen or small shop with a chest deep freezer",
    "washing-machine": "a Kashmir home laundry area with a washing machine",
    "ro-purifier": "a Kashmir home kitchen counter with an RO water purifier",
    "induction-heaters": "a Kashmir home kitchen or living space with a small appliance",
    "generators": "a Kashmir home courtyard or shop backyard with a petrol or diesel generator",
}

ACTION = {
    "ac-installation": "installing a split AC indoor unit carefully on the wall",
    "ac-repair": "diagnosing an AC indoor unit with tools and gauges",
    "ac-servicing": "cleaning AC filters and servicing the indoor unit",
    "ac-uninstallation": "safely uninstalling an AC outdoor unit",
    "gas-refill-check-up": "checking AC gas pressure with a manifold gauge",
    "inverter-installation": "installing a home inverter and battery connections",
    "inverter-repair": "diagnosing a home inverter display and wiring",
    "inverter-servicing": "servicing an inverter and checking battery terminals",
    "inverter-uninstallation": "safely uninstalling an inverter backup system",
    "cctv-installation": "installing a CCTV camera on a wall with drill and tools",
    "cctv-repair": "repairing a CCTV camera and checking the recorder",
    "geyser-installation": "installing a wall-mounted storage geyser",
    "geyser-repair": "repairing a bathroom geyser heating issue",
    "geyser-cleaning": "cleaning and flushing a bathroom geyser",
    "geyser-uninstallation": "uninstalling a wall geyser carefully",
    "tv-installation": "wall-mounting a smart TV with a bracket level",
    "tv-repair": "diagnosing a smart TV display and power issue",
    "tv-uninstallation": "uninstalling a wall-mounted TV carefully",
    "refrigerator-installation": "installing and leveling a home refrigerator",
    "refrigerator-repair": "diagnosing a refrigerator cooling fault",
    "gas-refill-leak-fix": "refilling refrigerator refrigerant with manifold gauges at the fridge compressor — not LPG cooking gas",
    "deep-freezer-installation": "installing and leveling a chest deep freezer",
    "deep-freezer-repair": "diagnosing a chest deep freezer cooling fault",
    "deep-freezer-gas-refill-leak-fix": "refilling deep freezer refrigerant with manifold gauges at the compressor — not LPG cooking gas",
    "washing-machine-installation": "installing a washing machine with inlet and drain setup",
    "washing-machine-repair": "repairing a washing machine drain or spin fault",
    "washing-machine-servicing": "deep cleaning and jet servicing a washing machine",
    "washing-machine-uninstallation": "uninstalling a washing machine carefully",
    "ro-installation": "installing an RO water purifier under a kitchen sink",
    "ro-service": "servicing an RO purifier and checking filters",
    "fan-repair": "repairing a ceiling fan regulator and motor",
    "microwave-repair": "diagnosing a microwave oven that is not heating",
    "induction-heater-repair": "repairing an induction cooktop on a kitchen counter",
    "oven-otg-repair": "repairing a kitchen OTG oven",
    "vacuum-cleaner-repair": "repairing a home vacuum cleaner suction issue",
    "mixer-grinder-repair": "repairing a kitchen mixer grinder",
    "chimney-repair": "repairing a kitchen chimney suction fan",
    "chimney-installation": "installing a kitchen chimney on the wall",
    "hob-repair": "repairing a gas hob on a kitchen counter",
    "hob-installation": "installing a built-in kitchen hob",
    "air-cooler-repair": "repairing a desert air cooler pump and fan",
    "room-heater-repair": "repairing a room oil heater",
    "dishwasher-repair": "diagnosing a built-in dishwasher fault",
    "dishwasher-installation": "installing a kitchen dishwasher with water connections",
    "generator-installation": "installing a home petrol or diesel generator and checking placement",
    "generator-repair": "diagnosing a generator that will not start, checking fuel and wiring",
    "generator-servicing": "servicing a generator with oil, filter, and engine checks",
    "generator-uninstallation": "safely disconnecting and uninstalling a home generator",
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
    print(f"Wrote {len(rows)} home appliances photo prompts to {OUT}")


if __name__ == "__main__":
    main()
