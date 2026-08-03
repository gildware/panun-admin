#!/usr/bin/env python3
"""AI icon prompts for home appliances categories + variants (Urban Company flat navy style)."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "home-appliances-catalog.php"
OUT = Path(__file__).resolve().parent / "data" / "home-appliances-icon-prompts.json"

ICON_STYLE = (
    "Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. "
    "Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered."
)

CATEGORY_PROMPTS = {
    "home-appliance": (
        "Category icon: home appliance toolkit with wrench and plug, professional appliance services. " + ICON_STYLE
    ),
    "air-conditioners": (
        "Category icon: split air conditioner indoor unit silhouette, AC services. " + ICON_STYLE
    ),
    "battery-inverters": (
        "Category icon: home inverter with battery pack silhouette, power backup services. " + ICON_STYLE
    ),
    "cctv": (
        "Category icon: security CCTV camera silhouette, surveillance services. " + ICON_STYLE
    ),
    "geysers": (
        "Category icon: wall mounted water geyser heater silhouette. " + ICON_STYLE
    ),
    "led-smart-tv": (
        "Category icon: flat smart TV screen silhouette, TV services. " + ICON_STYLE
    ),
    "refrigerators": (
        "Category icon: double door refrigerator silhouette. " + ICON_STYLE
    ),
    "washing-machine": (
        "Category icon: front load washing machine silhouette. " + ICON_STYLE
    ),
    "ro-purifier": (
        "Category icon: RO water purifier dispenser silhouette. " + ICON_STYLE
    ),
    "induction-heaters": (
        "Category icon: small kitchen appliances cluster microwave mixer chimney silhouette. " + ICON_STYLE
    ),
}

VARIANT_SUBJECTS = {
    "split-ac": "split AC indoor outdoor unit pair silhouette",
    "window-ac": "window air conditioner unit silhouette",
    "book-site-inspection": "clipboard checklist with magnifying glass site inspection icon",
    "lessno-cooling": "AC with weak cold air arrows silhouette",
    "power-issue": "power plug with warning spark silhouette",
    "unwanted-noisesmell": "AC with sound waves and odor lines silhouette",
    "water-leakage": "AC with water drip leak silhouette",
    "general-servicing": "AC filter cleaning brush service silhouette",
    "foam-jet-servicing": "AC foam jet deep cleaning spray silhouette",
    "gas-refill-check-up": "AC gas refill manifold gauge silhouette",
    "single-battery": "single inverter battery silhouette",
    "double-battery": "twin inverter batteries silhouette",
    "inverter-servicing": "inverter service tools and battery terminal silhouette",
    "inverter-uninstallation": "inverter removal disconnect silhouette",
    "storage-geyser": "storage water geyser tank silhouette",
    "instant-geyser": "instant water heater geyser silhouette",
    "no-heating": "appliance with no heat symbol silhouette",
    "leakage": "water leak drip from appliance silhouette",
    "thermostat-issue": "thermostat dial fault silhouette",
    "heating-element-issue": "heating element coil silhouette",
    "connection-leak": "pipe connection leak drip silhouette",
    "geyser-cleaning": "geyser tank cleaning brush silhouette",
    "geyser-uninstallation": "geyser removal wall bracket silhouette",
    "upto-30-inch": "small TV up to 30 inch screen silhouette",
    "32-to-43-inch": "medium TV 32 to 43 inch screen silhouette",
    "46-to-55-inch": "TV 46 to 55 inch screen silhouette",
    "56-to-65-inch": "large TV 56 to 65 inch screen silhouette",
    "over-75-inch": "extra large TV over 75 inch screen silhouette",
    "display-issue": "display screen with broken lines silhouette",
    "sound-issue": "speaker with muted sound waves silhouette",
    "upto-46-inch": "TV up to 46 inch uninstall silhouette",
    "over-65-inch": "large TV over 65 inch silhouette",
    "single-door": "single door refrigerator silhouette",
    "double-door": "double door refrigerator silhouette",
    "side-by-side": "side by side refrigerator silhouette",
    "french-door": "french door refrigerator silhouette",
    "cooling-issue": "fridge with weak cooling snowflake silhouette",
    "leak": "fridge water leak drip silhouette",
    "noise": "appliance with noise sound waves silhouette",
    "gas-refill-leak-fix": "refrigerator with refrigerant manifold gauges and snowflake silhouette",
    "front-load": "front load washing machine silhouette",
    "top-load": "top load washing machine silhouette",
    "semi-automatic": "semi automatic twin tub washer silhouette",
    "drain-issue": "washing machine drain hose blockage silhouette",
    "not-spinning-washing": "washer drum not spinning silhouette",
    "display-error": "appliance display error code silhouette",
    "descaling": "washing machine descaling powder bottle silhouette",
    "machine-cover": "washing machine protective cover silhouette",
    "ro-installation": "RO water purifier install silhouette",
    "filter-replacement": "RO filter cartridge replacement silhouette",
    "low-water-output": "RO with low water flow drops silhouette",
    "no-power": "appliance power off symbol silhouette",
    "ceiling-fan": "ceiling fan blades silhouette",
    "pedestal-table-fan": "pedestal table fan silhouette",
    "turntable-issue": "microwave turntable plate silhouette",
    "no-suction": "chimney with weak suction arrows silhouette",
    "light-issue": "chimney light bulb fault silhouette",
    "chimney-installation": "kitchen chimney install silhouette",
    "hob-installation": "kitchen gas hob install silhouette",
    "dishwasher-installation": "dishwasher install silhouette",
}


def load_catalog() -> dict:
    result = subprocess.run(
        ["php", "-r", f'echo json_encode(require "{CATALOG}");'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def variant_prompt(service_name: str, variant_key: str, title: str) -> str:
    subject = VARIANT_SUBJECTS.get(variant_key, f"{title.lower()} appliance service silhouette")
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
                    "title": var["title"],
                    "filename": filename,
                    "prompt": variant_prompt(svc["name"], key, var["title"]),
                }
            )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"categories": categories, "variants": variants}, indent=2))
    print(f"Wrote {len(categories)} category + {len(variants)} variant icon prompts to {OUT}")


if __name__ == "__main__":
    main()
