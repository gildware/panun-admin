#!/usr/bin/env python3
"""Generate Book Site Inspection variant icons for Aluminium & Steel Works services."""

from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageDraw

COLOR = "#1A233A"
SIZE = 512
SCALE = 2
OUT = Path(__file__).resolve().parent
CATALOG = Path(__file__).resolve().parents[2] / "data" / "aluminium-steel-catalog.php"


def s(v: int) -> int:
    return v * SCALE


def save_icon(name: str, drawer) -> None:
    big = SIZE * SCALE
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    drawer(ImageDraw.Draw(img))
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)


def draw_checkmark(draw, x1, y1, x2, y2, x3, y3, width):
    draw.line((x1, y1, x2, y2), fill=COLOR, width=width)
    draw.line((x2, y2, x3, y3), fill=COLOR, width=width)


def draw_lens_check(draw, cx, cy, diameter):
    half = s(diameter // 2)
    scx, scy = s(cx), s(cy)
    stroke = s(max(16, diameter // 10))
    draw.ellipse((scx - half, scy - half, scx + half, scy + half), outline=COLOR, width=stroke)
    hx = scx + int(half * 0.55)
    hy = scy + int(half * 0.55)
    draw.line((hx, hy, hx + s(int(diameter * 0.38)), hy + s(int(diameter * 0.38))), fill=COLOR, width=stroke + s(4))
    w = s(max(10, diameter // 14))
    draw_checkmark(draw, scx - s(diameter // 5), scy + s(diameter // 14), scx - s(diameter // 14), scy + s(diameter // 4),
                   scx + s(diameter // 4), scy - s(diameter // 5), w)


def draw_clipboard_pen(draw, x, y, w, h):
    sx, sy, sw, sh = s(x), s(y), s(w), s(h)
    draw.rounded_rectangle((sx, sy, sx + sw, sy + sh), radius=s(10), fill=COLOR)
    draw.rounded_rectangle((sx + s(16), sy - s(18), sx + sw - s(16), sy + s(4)), radius=s(5), fill=COLOR)
    draw.ellipse((sx + sw // 2 - s(10), sy - s(28), sx + sw // 2 + s(10), sy - s(8)), outline="white", width=s(4))
    row_y = sy + s(28)
    for _ in range(2):
        box = s(16)
        draw.rectangle((sx + s(14), row_y, sx + s(14) + box, row_y + box), outline="white", width=s(3))
        draw_checkmark(draw, sx + s(17), row_y + s(9), sx + s(21), row_y + s(13), sx + s(27), row_y + s(5), s(3))
        draw.rounded_rectangle((sx + s(38), row_y + s(4), sx + sw - s(12), row_y + s(12)), radius=s(2), fill="white")
        row_y += s(30)
    px1, py1 = sx + sw + s(8), sy + sh - s(40)
    px2, py2 = sx + sw + s(48), sy + s(20)
    draw.line((px1, py1, px2, py2), fill=COLOR, width=s(10))
    draw.polygon([(px2, py2), (px2 + s(10), py2 + s(4)), (px2 - s(2), py2 + s(12))], fill=COLOR)


def draw_window(draw):
    stroke = s(14)
    x1, y1, x2, y2 = s(62), s(120), s(200), s(290)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    cx, cy = (x1 + x2) // 2, (y1 + y2) // 2
    draw.line((cx, y1, cx, y2), fill=COLOR, width=stroke)
    draw.line((x1, cy, x2, cy), fill=COLOR, width=stroke)


def draw_door(draw):
    stroke = s(14)
    ox, oy, fw, fh = s(58), s(118), s(118), s(210)
    draw.rounded_rectangle((ox, oy, ox + fw, oy + fh), radius=s(6), outline=COLOR, width=stroke)
    draw.ellipse((ox + fw - s(28), oy + fh // 2 - s(10), ox + fw - s(8), oy + fh // 2 + s(10)), fill=COLOR)


def draw_panels(draw):
    stroke = s(12)
    for i, px in enumerate([s(58), s(98), s(138), s(178)]):
        draw.rectangle((px, s(120), px + s(28), s(300)), outline=COLOR, width=stroke)
        if i == 1:
            draw.line((px + s(6), s(150), px + s(22), s(190)), fill=COLOR, width=s(6))


def draw_railing(draw):
    stroke = s(12)
    draw.line((s(58), s(280), s(204), s(280)), fill=COLOR, width=stroke)
    for px in range(s(70), s(200), s(28)):
        draw.line((px, s(140), px, s(280)), fill=COLOR, width=stroke // 2)


def draw_gate(draw):
    stroke = s(12)
    draw.rectangle((s(64), s(130), s(204), s(300)), outline=COLOR, width=stroke)
    for px in range(s(80), s(190), s(24)):
        draw.line((px, s(130), px, s(300)), fill=COLOR, width=stroke // 2)


def draw_ceiling(draw):
    stroke = s(10)
    for py in range(s(140), s(280), s(28)):
        draw.line((s(60), py, s(204), py), fill=COLOR, width=stroke // 2)
    draw.rectangle((s(60), s(130), s(204), s(290)), outline=COLOR, width=stroke)


def draw_shutter(draw):
    for py in range(s(120), s(290), s(22)):
        draw.rectangle((s(64), py, s(204), py + s(14)), fill=COLOR)


def draw_structure(draw):
    draw.line((s(60), s(280), s(204), s(280)), fill=COLOR, width=s(12))
    for px in range(s(70), s(200), s(40)):
        draw.line((px, s(120), px, s(280)), fill=COLOR, width=s(10))


def draw_bracket(draw):
    draw.line((s(80), s(280), s(80), s(160)), fill=COLOR, width=s(14))
    draw.line((s(80), s(160), s(190), s(160)), fill=COLOR, width=s(14))
    draw.polygon([(s(190), s(160)), (s(170), s(200)), (s(210), s(200))], fill=COLOR)


def subject_for_slug(slug: str):
    if "acp" in slug or "pvc" in slug and "panel" in slug:
        return draw_panels
    if "window" in slug:
        return draw_window
    if "door" in slug:
        return draw_door
    if "railing" in slug:
        return draw_railing
    if "gate" in slug or "grill" in slug:
        return draw_gate
    if "false-ceiling" in slug:
        return draw_ceiling
    if "shutter" in slug:
        return draw_shutter
    if "pergola" in slug or "signage" in slug:
        return draw_structure
    if "bracket" in slug or "fabrication" in slug:
        return draw_bracket
    if "glass" in slug:
        return draw_window
    return draw_panels


def compose(subject_drawer):
    def _draw(draw):
        subject_drawer(draw)
        draw_lens_check(draw, 198, 248, 132)
        draw_clipboard_pen(draw, 292, 132, 108, 148)

    return _draw


def parse_slugs() -> list[str]:
    text = CATALOG.read_text()
    return re.findall(r"'slug'\s*=>\s*'([^']+)'", text)


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    slugs = [s for s in parse_slugs() if s not in (
        'aluminium-steel-works', 'metal-works-installation', 'metal-works-repairs', 'metal-works-fabrication',
        'book-site-inspection',
    )]
    for slug in slugs:
        name = f"{slug}-book-site-inspection"
        save_icon(name, compose(subject_for_slug(slug)))
    save_icon("book-site-inspection", compose(draw_window))
    print(f"Generated {len(slugs) + 1} variant icons")


if __name__ == "__main__":
    main()
