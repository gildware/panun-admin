#!/usr/bin/env python3
"""Build name-matched AI prompts for masonry / home-appliance / womens-salon / gardening variants."""

from __future__ import annotations

import json
import re
from pathlib import Path

SRC = Path("/tmp/pk-fourcat-variants.json")
OUT = Path("/tmp/pk-fourcat-icon-prompts.json")

ICON_STYLE = (
    "Flat filled vector mobile app icon, perfect square 1:1 composition with equal padding. "
    "Solid dark navy blue #1A233A shapes ONLY on pure white background. "
    "Bold minimalist geometric Urban Company style. "
    "No text, no letters, no numbers, no brand logos, no gradients, no shadows, no gray, centered."
)

# Category-scoped keyword → visual subject hints (order matters within a category)
MASONRY_HINTS: list[tuple[re.Pattern[str], str]] = [
    (re.compile(r"brick", re.I), "brick wall stack and mason trowel"),
    (re.compile(r"tile", re.I), "floor and wall tiles with tiling trowel"),
    (re.compile(r"marble", re.I), "polished marble slab and mason tools"),
    (re.compile(r"stone", re.I), "natural stone blocks and mason hammer"),
    (re.compile(r"plaster", re.I), "wall plaster trowel and hawk board"),
    (re.compile(r"stair", re.I), "masonry staircase steps and trowel"),
    (re.compile(r"boundary", re.I), "boundary brick wall with cement trowel"),
    (re.compile(r"waterproof", re.I), "waterproofing membrane brush on wall"),
    (re.compile(r"damp", re.I), "damp wall stain with repair sealant"),
    (re.compile(r"crack", re.I), "cracked masonry wall with repair patch"),
    (re.compile(r"bathroom", re.I), "bathroom tile masonry and basin outline"),
    (re.compile(r"safety|pre.?work|renovation|unknown|inspection|check", re.I), "clipboard checklist brick and magnifying glass"),
    (re.compile(r"repair", re.I), "cracked wall with mason repair trowel"),
    (re.compile(r"install", re.I), "new brick install with mason trowel"),
]

GARDENING_HINTS: list[tuple[re.Pattern[str], str]] = [
    (re.compile(r"lawn|mow", re.I), "lawn mower cutting grass strip"),
    (re.compile(r"grass|edging|levell", re.I), "grass edger and neat lawn edge"),
    (re.compile(r"hedge", re.I), "hedge shears trimming a bush"),
    (re.compile(r"prun|shrub|tree|deadhead|shaping", re.I), "pruning shears cutting shrub branch"),
    (re.compile(r"weed|cleanup", re.I), "garden weeding tools and pulled weeds"),
    (re.compile(r"leaf|debris", re.I), "garden rake with leaf debris pile"),
    (re.compile(r"drip|irrigation", re.I), "drip irrigation tube with water drops"),
    (re.compile(r"repot|planting", re.I), "potted plant with garden trowel"),
    (re.compile(r"soil|fertiliz", re.I), "soil mound with fertilizer scoop"),
    (re.compile(r"terrace|balcony", re.I), "balcony planter pots and watering can"),
    (re.compile(r"pest|disease", re.I), "plant leaf with pest spray bottle"),
    (re.compile(r"maintenance|seasonal", re.I), "garden calendar with plant and trowel"),
    (re.compile(r"inspection|site", re.I), "clipboard with plant and magnifying glass"),
]

SALON_HINTS: list[tuple[re.Pattern[str], str]] = [
    (re.compile(r"wax", re.I), "waxing strip and spatula"),
    (re.compile(r"thread", re.I), "threading spool shaping an eyebrow"),
    (re.compile(r"bleach|dtan|o3|vlcc|lotus|pearl|herbal|kanepeki|diamond|anti.?aging|anti.?tan|facial|cleanup", re.I), "facial cream jar and soft face brush"),
    (re.compile(r"manicure|\bmani\b", re.I), "manicure hand with nail file"),
    (re.compile(r"pedicure|\bpedi\b", re.I), "pedicure foot with nail polish bottle"),
    (re.compile(r"cut.?file.?polish|nail", re.I), "nail clipper file and polish bottle"),
    (re.compile(r"layered|step.?cut|u-?cut|v-?cut|flick|hair.?cut|styling|straighten|curl|wave", re.I), "scissors and comb hair styling"),
    (re.compile(r"keratin|botox|hair.?spa|highlight|henna|mehendi|streak|root.?touch|hair.?color", re.I), "hair treatment bottle and coloring brush"),
    (re.compile(r"wash|blow.?dry", re.I), "shampoo bottle and hair dryer"),
    (re.compile(r"massage|body.?polish", re.I), "massage hands with oil drop"),
    (re.compile(r"package", re.I), "beauty spa package box with leaf"),
]

APPLIANCE_HINTS: list[tuple[re.Pattern[str], str]] = [
    (re.compile(r"split.?ac|window.?ac|air.?condition|\bac\b", re.I), "split air conditioner indoor unit"),
    (re.compile(r"geyser|water.?heater", re.I), "wall mounted water geyser"),
    (re.compile(r"refrigerat|fridge|french.?door|side.?by.?side|single.?door|double.?door", re.I), "refrigerator silhouette"),
    (re.compile(r"washing.?machine|front.?load|top.?load|semi.?automatic|descaling|machine.?cover", re.I), "washing machine front"),
    (re.compile(r"\btv\b|television|smart.?tv|inch", re.I), "flat screen TV"),
    (re.compile(r"\bro\b|purifier|filter.?replacement|low.?water", re.I), "RO water purifier"),
    (re.compile(r"inverter|battery", re.I), "home inverter with battery"),
    (re.compile(r"cctv", re.I), "CCTV security camera"),
    (re.compile(r"microwave|turntable", re.I), "microwave oven"),
    (re.compile(r"chimney|suction", re.I), "kitchen chimney hood"),
    (re.compile(r"dishwasher", re.I), "dishwasher appliance"),
    (re.compile(r"\bhob\b", re.I), "gas hob cooktop"),
    (re.compile(r"induction", re.I), "induction cooktop"),
    (re.compile(r"mixer|grinder", re.I), "mixer grinder"),
    (re.compile(r"oven|otg", re.I), "kitchen oven"),
    (re.compile(r"fan\b|ceiling.?fan|pedestal", re.I), "ceiling fan"),
    (re.compile(r"vacuum", re.I), "vacuum cleaner"),
    (re.compile(r"air.?cooler|cooler", re.I), "air cooler"),
    (re.compile(r"room.?heater|heater", re.I), "room heater"),
    (re.compile(r"gas.?refill|leak.?fix", re.I), "gas refill gauge manifold"),
    (re.compile(r"foam.?jet|servicing", re.I), "appliance foam cleaning spray service"),
    (re.compile(r"uninstall", re.I), "appliance removal with disconnect tools"),
    (re.compile(r"install", re.I), "appliance installation with fitting tools"),
    (re.compile(r"repair|noise|leak|power.?issue|cooling|heating|drain|display|thermostat|spinning", re.I), "repair wrench beside appliance fault mark"),
    (re.compile(r"inspection|site", re.I), "clipboard checklist with magnifying glass"),
]

CATEGORY_HINTS = {
    "masonry": MASONRY_HINTS,
    "gardening": GARDENING_HINTS,
    "womens-salon": SALON_HINTS,
    "home-appliance": APPLIANCE_HINTS,
}


def subject_for(category: str, service_name: str, variant_title: str, variant_key: str) -> str:
    blob = f"{service_name} {variant_title} {variant_key}"
    for pat, subject in CATEGORY_HINTS.get(category, []):
        if pat.search(blob):
            return subject
    clean = re.sub(r"[^a-zA-Z0-9\s&/-]+", " ", f"{service_name} {variant_title}").strip()
    return f"simple geometric silhouette representing {clean}"


def build_prompt(row: dict) -> str:
    service = row["service_name"]
    variant = row["variant_title"]
    subject = subject_for(row["category_slug"], service, variant, row["variant_key"])
    return (
        f"Variation icon for service '{service}', option '{variant}': "
        f"large centered {subject}. Icon must clearly match the service and variation name. "
        f"{ICON_STYLE}"
    )


def main() -> None:
    rows = json.loads(SRC.read_text())
    for row in rows:
        row["prompt"] = build_prompt(row)
        row["status"] = "regen_ai"
    OUT.write_text(json.dumps(rows, indent=2, ensure_ascii=False))
    print(f"Wrote {len(rows)} prompts -> {OUT}")
    by = {}
    for r in rows:
        by[r["category_slug"]] = by.get(r["category_slug"], 0) + 1
    print(by)


if __name__ == "__main__":
    main()
