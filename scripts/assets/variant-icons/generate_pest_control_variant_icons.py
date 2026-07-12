#!/usr/bin/env python3
"""Generate pest control variant icons — carpentry ref style (#1A233A, subject + lens + clipboard)."""

from __future__ import annotations

import importlib.util
import json
import math
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageDraw

SCRIPTS = Path(__file__).resolve().parents[2]
CATALOG = SCRIPTS / "data" / "pest-control-catalog.php"
OUT = Path(__file__).resolve().parent
REF = Path(__file__).resolve().parent / "generate_ref_style_samples.py"

SIZE = 512
SCALE = 2
COLOR = "#1A233A"


def load_ref():
    spec = importlib.util.spec_from_file_location("ref_samples", REF)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


REF = load_ref()
s = REF.s


def save_icon(name: str, drawer) -> None:
    big = SIZE * SCALE
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    drawer(ImageDraw.Draw(img))
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def compose(subject) -> None:
    def _draw(draw: ImageDraw.ImageDraw) -> None:
        subject(draw)
        REF.draw_lens_check(draw, 198, 248, 132)
        REF.draw_clipboard_pen(draw, 292, 132, 108, 148, pen_side="right")

    return _draw


def draw_apartment(draw: ImageDraw.ImageDraw, floors: int = 2) -> None:
    stroke = s(14)
    x1, y1, x2, y2 = s(52), s(108), s(198), s(300)
    draw.rounded_rectangle((x1, y1, x2, y2), radius=s(8), outline=COLOR, width=stroke)
    rows = min(4, max(1, floors))
    for row in range(rows):
        for col in range(2):
            wx = x1 + s(20) + col * s(54)
            wy = y1 + s(18) + row * s(44)
            draw.rounded_rectangle((wx, wy, wx + s(36), wy + s(28)), radius=s(3), outline=COLOR, width=s(6))
            draw.line((wx + s(8), wy + s(14), wx + s(28), wy + s(14)), fill=COLOR, width=s(3))
    draw.rectangle((x1 + s(70), y2 - s(8), x1 + s(98), y2 + s(4)), fill=COLOR)


def draw_bungalow(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    draw.polygon([(s(56), s(286)), (s(128), s(108)), (s(200), s(286))], outline=COLOR, width=stroke)
    draw.rectangle((s(84), s(198), s(172), s(286)), outline=COLOR, width=stroke)
    draw.rectangle((s(112), s(232), s(144), s(286)), fill=COLOR)
    draw.rectangle((s(58), s(250), s(84), s(276)), outline=COLOR, width=s(8))
    draw.rectangle((s(172), s(250), s(198), s(276)), outline=COLOR, width=s(8))


def draw_kitchen(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    x1, y1, x2, y2 = s(58), s(128), s(200), s(300)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    draw.line((x1, s(214), x2, s(214)), fill=COLOR, width=stroke)
    draw.ellipse((s(122), s(244), s(142), s(264)), outline=COLOR, width=s(8))
    draw.line((x1 + s(14), s(170), x2 - s(14), s(170)), fill="white", width=s(4))


def draw_bedroom(draw: ImageDraw.ImageDraw, beds: int = 1) -> None:
    stroke = s(14)
    for i in range(min(beds, 2)):
        ox = s(54) + i * s(34)
        draw.rounded_rectangle((ox, s(206), ox + s(112), s(300)), radius=s(8), outline=COLOR, width=stroke)
        draw.arc((ox + s(18), s(162), ox + s(94), s(224)), 0, 180, fill=COLOR, width=stroke)
        draw.line((ox + s(18), s(244), ox + s(94), s(244)), fill="white", width=s(4))


def draw_bedroom_kitchen(draw: ImageDraw.ImageDraw, beds: int) -> None:
    draw_bedroom(draw, beds)
    stroke = s(12)
    x1, y1, x2, y2 = s(118), s(148), s(198), s(300)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    draw.line((x1, s(228), x2, s(228)), fill=COLOR, width=stroke)


def draw_balcony(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    x1, y1, x2, y2 = s(54), s(168), s(198), s(300)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    for x in range(x1 + s(16), x2 - s(8), s(26)):
        draw.line((x, y1, x, y2), fill=COLOR, width=stroke // 2)
    draw.rectangle((x1, y1 - s(10), x2, y1 + s(6)), fill=COLOR)


def draw_bathroom(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    draw.arc((s(78), s(206), s(178), s(292)), 180, 360, fill=COLOR, width=stroke)
    draw.rectangle((s(78), s(248), s(178), s(292)), fill=COLOR)
    draw.rectangle((s(122), s(118), s(138), s(206)), fill=COLOR)
    draw.ellipse((s(148), s(132), s(164), s(148)), outline="white", width=s(4))


def draw_extra_room(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    draw.rounded_rectangle((s(66), s(148), s(188), s(300)), radius=s(8), outline=COLOR, width=stroke)
    draw.line((s(127), s(188), s(127), s(262)), fill=COLOR, width=stroke)
    draw.line((s(96), s(225), s(158), s(225)), fill=COLOR, width=stroke)


def draw_addon_check(draw: ImageDraw.ImageDraw, checked: bool) -> None:
    stroke = s(14)
    cx, cy, r = s(126), s(218), s(54)
    draw.ellipse((cx - r, cy - r, cx + r, cy + r), outline=COLOR, width=stroke)
    if checked:
        REF.draw_checkmark(draw, cx - s(24), cy + s(4), cx - s(6), cy + s(24), cx + s(28), cy - s(20), s(8))
    else:
        draw.line((cx - s(20), cy - s(20), cx + s(20), cy + s(20)), fill=COLOR, width=stroke)


def draw_area_plan(draw: ImageDraw.ImageDraw, size: str = "medium") -> None:
    stroke = s(12)
    x1, y1, x2, y2 = s(54), s(118), s(198), s(292)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    draw.line((x1, y1, x2, y2), fill=COLOR, width=stroke)
    draw.line((x1, y2, x2, y1), fill=COLOR, width=stroke)
    labels = {"small": "S", "medium": "M", "large": "L", "xl": "XL"}
    draw.text((s(126), s(205)), labels.get(size, "M"), fill=COLOR, anchor="mm")


def draw_office_block(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    x1, y1, x2, y2 = s(58), s(108), s(198), s(300)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    for row in range(4):
        y = y1 + s(28) + row * s(42)
        draw.line((x1, y, x2, y), fill=COLOR, width=s(6))
        for col in range(3):
            wx = x1 + s(16) + col * s(44)
            draw.rectangle((wx, y + s(8), wx + s(28), y + s(28)), fill=COLOR if (row + col) % 2 else "white", outline=COLOR, width=s(3))


def draw_rodent(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    draw.ellipse((s(66), s(198), s(186), s(282)), outline=COLOR, width=stroke)
    draw.ellipse((s(166), s(208), s(198), s(238)), fill=COLOR)
    draw.line((s(66), s(240), s(40), s(260)), fill=COLOR, width=stroke)
    draw.ellipse((s(88), s(210), s(104), s(226)), fill="white")


def draw_ant(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    cx, cy = s(126), s(238)
    draw.ellipse((cx - s(22), cy - s(18), cx + s(22), cy + s(18)), fill=COLOR)
    for dx in (-s(32), 0, s(32)):
        draw.line((cx + dx, cy, cx + dx, cy + s(42)), fill=COLOR, width=stroke)
    draw.line((cx - s(32), cy + s(42), cx - s(48), cy + s(58)), fill=COLOR, width=stroke)
    draw.line((cx + s(32), cy + s(42), cx + s(48), cy + s(58)), fill=COLOR, width=stroke)


def draw_dining_seats(draw: ImageDraw.ImageDraw, seats: str) -> None:
    stroke = s(12)
    draw.ellipse((s(86), s(188), s(166), s(248)), outline=COLOR, width=stroke)
    count = {"up-to-20-seats": 2, "21-50-seats": 3, "51-100-seats": 4, "100-plus-seats": 5}.get(seats, 3)
    for i in range(count):
        rad = math.radians(35 + i * (290 / max(count, 1)))
        px = s(126) + int(math.cos(rad) * s(56))
        py = s(218) + int(math.sin(rad) * s(38))
        draw.rounded_rectangle((px - s(10), py - s(10), px + s(10), py + s(10)), radius=s(3), fill=COLOR)


def draw_kitchen_size(draw: ImageDraw.ImageDraw, size: str) -> None:
    widths = {"small-kitchen": 88, "medium-kitchen": 112, "large-kitchen": 132, "kitchen-storage": 132}
    w = s(widths.get(size, 112))
    x1, y1 = s(126) - w // 2, s(128)
    x2, y2 = s(126) + w // 2, s(300)
    stroke = s(14)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    draw.line((x1, s(214), x2, s(214)), fill=COLOR, width=stroke)
    if size == "kitchen-storage":
        draw.rectangle((x2 - s(8), y1 + s(36), x2 + s(28), y2 - s(18)), outline=COLOR, width=stroke)


def subject_for(service_slug: str, variant_key: str):
    if variant_key in {"1-bhk", "2-bhk", "3-bhk", "4-bhk"}:
        floors = int(variant_key[0])
        return lambda d: draw_apartment(d, floors)
    if variant_key in {"2000-3000-sq-ft", "3000-4000-sq-ft"}:
        return draw_bungalow
    if variant_key in {"4000-5000-sq-ft", "5000-sq-ft"}:
        return lambda d: draw_area_plan(d, "large")
    if variant_key in {"1-bathroom-and-kitchen", "kitchen-only"}:
        return draw_kitchen
    if variant_key.endswith("-bedroom-kitchen"):
        beds = int(variant_key[0])
        return lambda d: draw_bedroom_kitchen(d, beds)
    if variant_key == "balcony":
        return draw_balcony
    if variant_key == "bathroom":
        return draw_bathroom
    if variant_key == "bedroom":
        return lambda d: draw_bedroom(d, 1)
    if variant_key == "extra-room":
        return draw_extra_room
    if variant_key == "not-required":
        return lambda d: draw_addon_check(d, False)
    if variant_key == "yes":
        return lambda d: draw_addon_check(d, True)
    if variant_key == "up-to-500-sq-ft":
        return lambda d: draw_area_plan(d, "small")
    if variant_key == "500-1000-sq-ft":
        return lambda d: draw_area_plan(d, "medium")
    if variant_key == "1000-2000-sq-ft":
        return lambda d: draw_area_plan(d, "large")
    if variant_key == "2000-sq-ft":
        return lambda d: draw_area_plan(d, "xl")
    if variant_key in {"small-kitchen", "medium-kitchen", "large-kitchen", "kitchen-storage"}:
        return lambda d: draw_kitchen_size(d, variant_key)
    if variant_key.endswith("-seats"):
        return lambda d: draw_dining_seats(d, variant_key)
    if "rodent" in service_slug:
        return draw_rodent
    if "ant" in service_slug:
        return draw_ant
    if "office" in service_slug:
        return draw_office_block
    if "restaurant" in service_slug:
        return draw_kitchen
    return draw_apartment


def load_catalog() -> dict:
    result = subprocess.run(
 ["php", "-r", f'echo json_encode(require "{CATALOG}");'],
        capture_output=True,
        text=True,
        check=True,
    )
    return json.loads(result.stdout)


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    catalog = load_catalog()
    count = 0
    for svc in catalog["services"]:
        slug = svc["slug"]
        for var in svc["variants"]:
            key = var["variant_key"]
            save_icon(f"{slug}-{key}", compose(subject_for(slug, key)))
            count += 1
    print(f"Generated {count} pest control variant icons (ref style)")


if __name__ == "__main__":
    main()
