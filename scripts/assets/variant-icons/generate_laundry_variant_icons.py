#!/usr/bin/env python3
"""Generate laundry variant icons from variation_label — navy #1A233A subject + label badge."""

from __future__ import annotations

import json
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

SIZE = 512
SCALE = 2
COLOR = "#1A233A"
MANIFEST = Path(__file__).resolve().parents[2] / "data" / "laundry-catalog-manifest.json"
OUT = Path(__file__).resolve().parent


def s(v: int) -> int:
    return v * SCALE


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/Library/Fonts/Arial.ttf",
    ]
    for path in candidates:
        if Path(path).exists():
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def save_icon(name: str, drawer) -> None:
    big = SIZE * SCALE
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    drawer(ImageDraw.Draw(img))
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def wrap_label(text: str, max_chars: int = 18) -> list[str]:
    words = text.split()
    if len(text) <= max_chars:
        return [text]
    lines: list[str] = []
    current: list[str] = []
    for word in words:
        trial = " ".join(current + [word])
        if len(trial) <= max_chars:
            current.append(word)
        else:
            if current:
                lines.append(" ".join(current))
            current = [word]
    if current:
        lines.append(" ".join(current))
    return lines[:2]


def draw_label_badge(draw: ImageDraw.ImageDraw, label: str) -> None:
    words = label.split()
    if len(words) >= 3:
        mid = len(words) // 2
        lines = [" ".join(words[:mid]), " ".join(words[mid:])]
    elif len(label) > 12 and len(words) == 2:
        lines = words
    else:
        lines = [label]

    size = 30 if len(lines) > 1 or max(len(line) for line in lines) > 10 else 36
    f = font(s(size), bold=True)
    line_h = s(32)
    pad_y = s(10)
    box_w = s(440)
    box_h = int(line_h * len(lines) + pad_y * 2)
    x1 = s(256) - box_w // 2
    y1 = s(410) - box_h // 2
    x2 = x1 + box_w
    y2 = y1 + box_h
    draw.rounded_rectangle((x1, y1, x2, y2), radius=s(12), fill=COLOR)
    ty = y1 + pad_y
    for line in lines:
        draw.text((s(256), ty + line_h // 2), line, fill="white", font=f, anchor="mm")
        ty += line_h


def with_label(subject_drawer, label: str):
    def _draw(draw: ImageDraw.ImageDraw) -> None:
        subject_drawer(draw)
        draw_label_badge(draw, label)

    return _draw


def draw_suit_pieces(draw: ImageDraw.ImageDraw, pieces: int) -> None:
    stroke = s(12)
    cx = s(256)
    draw.line((cx, s(72), cx, s(118)), fill=COLOR, width=stroke)
    # Jacket
    jx1, jx2 = s(188), s(324)
    draw.polygon([(cx, s(118)), (jx1, s(158)), (jx1, s(300)), (jx2, s(300)), (jx2, s(158))], outline=COLOR, width=stroke)
    draw.line((cx, s(158), cx, s(300)), fill=COLOR, width=s(6))
    if pieces >= 3:
        draw.rectangle((s(228), s(188), s(284), s(300)), fill=COLOR)
        draw.line((s(256), s(188), s(256), s(300)), fill="white", width=s(4))
    # Trousers
    px1, px2 = s(214), s(298)
    draw.rectangle((px1, s(300), px2, s(360)), outline=COLOR, width=stroke)
    draw.line((s(256), s(300), s(256), s(360)), fill=COLOR, width=s(5))


def draw_shirt_hanger(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    cx = s(256)
    draw.line((cx, s(70), cx, s(112)), fill=COLOR, width=stroke)
    draw.arc((s(188), s(96), s(324), s(150)), 180, 0, fill=COLOR, width=stroke)
    draw.polygon([(cx, s(130)), (s(176), s(168)), (s(176), s(340)), (s(336), s(340)), (s(336), s(168))], outline=COLOR, width=stroke)
    draw.line((cx, s(168), cx, s(340)), fill=COLOR, width=s(5))
    for y in range(s(210), s(320), s(28)):
        draw.line((s(196), y, s(316), y), fill=COLOR, width=s(3))


def draw_saree(draw: ImageDraw.ImageDraw, with_blouse: bool = False) -> None:
    stroke = s(12)
    draw.polygon([(s(256), s(78)), (s(168), s(340)), (s(344), s(340))], fill=COLOR)
    for x in range(s(188), s(324), s(22)):
        draw.line((x, s(120), x, s(340)), fill="white", width=s(3))
    if with_blouse:
        draw.rounded_rectangle((s(286), s(150), s(344), s(230)), radius=s(8), outline=COLOR, width=stroke)
        draw.line((s(300), s(170), s(330), s(170)), fill=COLOR, width=s(4))


def draw_blazer(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    cx = s(256)
    draw.line((cx, s(70), cx, s(112)), fill=COLOR, width=stroke)
    draw.polygon([(cx, s(112)), (s(170), s(160)), (s(170), s(350)), (s(342), s(350)), (s(342), s(160))], outline=COLOR, width=stroke)
    draw.line((cx, s(160), cx, s(350)), fill="white", width=s(5))
    draw.line((s(198), s(220), s(314), s(220)), fill=COLOR, width=s(5))


def draw_sweater(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    draw.rounded_rectangle((s(176), s(130), s(336), s(340)), radius=s(20), outline=COLOR, width=stroke)
    for y in range(s(165), s(320), s(26)):
        draw.line((s(190), y, s(322), y), fill=COLOR, width=s(5))
    draw.arc((s(206), s(90), s(306), s(150)), 180, 0, fill=COLOR, width=stroke)


def draw_woolen_coat(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    cx = s(256)
    draw.line((cx, s(68), cx, s(108)), fill=COLOR, width=stroke)
    draw.polygon([(cx, s(108)), (s(162), s(158)), (s(162), s(360)), (s(350), s(360)), (s(350), s(158))], outline=COLOR, width=stroke)
    draw.line((cx, s(158), cx, s(360)), fill=COLOR, width=s(5))
    draw.line((s(162), s(250), s(350), s(250)), fill=COLOR, width=s(8))
    draw.rectangle((s(300), s(250), s(336), s(360)), fill=COLOR)


def draw_shawl(draw: ImageDraw.ImageDraw, fine: bool = False) -> None:
    draw.polygon([(s(150), s(210)), (s(362), s(130)), (s(362), s(340)), (s(150), s(340))], fill=COLOR)
    step = s(14 if fine else 20)
    for y in range(s(170), s(320), step):
        draw.line((s(170), y, s(342), y - s(10)), fill="white", width=s(3))
    if fine:
        draw.arc((s(220), s(120), s(292), s(170)), 180, 0, fill=COLOR, width=s(8))


def draw_washer_load(draw: ImageDraw.ImageDraw, level: str) -> None:
    stroke = s(12)
    draw.rounded_rectangle((s(168), s(120), s(344), s(340)), radius=s(18), outline=COLOR, width=stroke)
    draw.ellipse((s(206), s(190), s(306), s(290)), outline=COLOR, width=stroke)
    draw.rectangle((s(228), s(88), s(284), s(122)), fill=COLOR)
    heights = {"low": s(250), "mid": s(230), "high": s(210)}
    hy = heights.get(level, s(230))
    draw.arc((s(214), hy, s(298), s(286)), 0, 180, fill=COLOR, width=s(10))


def draw_iron_stack(draw: ImageDraw.ImageDraw, count: int) -> None:
    draw.polygon([(s(256), s(88)), (s(332), s(168)), (s(180), s(168))], fill=COLOR)
    draw.rectangle((s(228), s(168), s(284), s(340)), fill=COLOR)
    base_x, base_y = s(300), s(190)
    for i in range(count):
        y = base_y + i * s(18)
        draw.rounded_rectangle((base_x, y, base_x + s(92), y + s(52)), radius=s(6), outline=COLOR, width=s(8))


def draw_window_curtains(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    draw.rectangle((s(168), s(110), s(344), s(340)), outline=COLOR, width=stroke)
    draw.line((s(256), s(110), s(256), s(340)), fill=COLOR, width=s(6))
    for x in range(s(188), s(248), s(16)):
        draw.line((x, s(130), x, s(340)), fill=COLOR, width=s(5))
    for x in range(s(264), s(324), s(16)):
        draw.line((x, s(130), x, s(340)), fill=COLOR, width=s(5))


def draw_door_curtain(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    draw.rectangle((s(206), s(90), s(306), s(360)), outline=COLOR, width=stroke)
    for x in range(s(220), s(292), s(18)):
        draw.line((x, s(100), x, s(360)), fill=COLOR, width=s(6))
    draw.line((s(150), s(90), s(362), s(90)), fill=COLOR, width=stroke)


def draw_bed(draw: ImageDraw.ImageDraw, width: int, pillows: int) -> None:
    stroke = s(12)
    x1 = s(256 - width // 2)
    x2 = s(256 + width // 2)
    draw.rounded_rectangle((x1, s(230), x2, s(340)), radius=s(12), outline=COLOR, width=stroke)
    draw.rectangle((x1, s(190), x2, s(230)), fill=COLOR)
    px = x1 + s(24)
    gap = (x2 - x1 - s(48)) // max(pillows, 1)
    for i in range(pillows):
        px1 = px + i * gap
        draw.rounded_rectangle((px1, s(160), px1 + s(42), s(200)), radius=s(8), fill=COLOR)


def draw_blanket(draw: ImageDraw.ImageDraw, width: int) -> None:
    x1 = s(256 - width // 2)
    x2 = s(256 + width // 2)
    draw.rounded_rectangle((x1, s(150), x2, s(340)), radius=s(22), fill=COLOR)
    for y in range(s(185), s(320), s(24)):
        draw.line((x1 + s(16), y, x2 - s(16), y), fill="white", width=s(3))


def draw_lehenga(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    cx = s(256)
    draw.line((cx, s(68), cx, s(108)), fill=COLOR, width=stroke)
    draw.polygon([(cx, s(108)), (s(196), s(168)), (s(168), s(360)), (s(344), s(360)), (s(316), s(168))], outline=COLOR, width=stroke)
    for y in range(s(210), s(340), s(20)):
        draw.line((s(188), y, s(324), y), fill=COLOR, width=s(4))
    draw.line((cx, s(168), cx, s(360)), fill="white", width=s(4))


def drawer_for_variant(variant_key: str, variation_label: str):
    key = variant_key
    label = variation_label

    if key == "2-piece-suit":
        return with_label(lambda d: draw_suit_pieces(d, 2), label)
    if key == "3-piece-suit":
        return with_label(lambda d: draw_suit_pieces(d, 3), label)
    if key == "shirt-dry-clean":
        return with_label(draw_shirt_hanger, label)
    if key == "saree-dry-clean":
        return with_label(lambda d: draw_saree(d, False), label)
    if key == "saree-with-blouse":
        return with_label(lambda d: draw_saree(d, True), label)
    if key == "blazer-dry-clean":
        return with_label(draw_blazer, label)
    if key == "sweater":
        return with_label(draw_sweater, label)
    if key == "woolen-coat":
        return with_label(draw_woolen_coat, label)
    if key == "shawl":
        return with_label(lambda d: draw_shawl(d, False), label)
    if key == "pashmina":
        return with_label(lambda d: draw_shawl(d, True), label)
    if key == "up-to-5-kg":
        return with_label(lambda d: draw_washer_load(d, "low"), label)
    if key == "5-10-kg":
        return with_label(lambda d: draw_washer_load(d, "mid"), label)
    if key == "10-15-kg":
        return with_label(lambda d: draw_washer_load(d, "high"), label)
    if key == "up-to-10-pieces":
        return with_label(lambda d: draw_iron_stack(d, 2), label)
    if key == "11-20-pieces":
        return with_label(lambda d: draw_iron_stack(d, 4), label)
    if key == "window-set":
        return with_label(draw_window_curtains, label)
    if key == "door-curtain":
        return with_label(draw_door_curtain, label)
    if key == "single-bed":
        return with_label(lambda d: draw_bed(d, 120, 1), label)
    if key == "double-bed":
        return with_label(lambda d: draw_bed(d, 170, 2), label)
    if key == "king-bed":
        return with_label(lambda d: draw_bed(d, 220, 3), label)
    if key == "single-blanket":
        return with_label(lambda d: draw_blanket(d, 120), label)
    if key == "double-blanket":
        return with_label(lambda d: draw_blanket(d, 180), label)
    if key in {"lehenga-dry-clean", "default"}:
        return with_label(draw_lehenga, label or "Lehenga")

    return with_label(draw_shirt_hanger, label or key.replace("-", " ").title())


def main() -> None:
    if not MANIFEST.is_file():
        print(f"Missing manifest: {MANIFEST}", file=sys.stderr)
        sys.exit(1)

    data = json.loads(MANIFEST.read_text())
    OUT.mkdir(parents=True, exist_ok=True)
    count = 0

    for svc in data["services"]:
        slug = svc["slug"]
        for variant in svc["variants"]:
            variant_key = variant["variant_key"]
            label = variant.get("variation_label") or variant.get("title", variant_key)
            file_key = f"{slug}-{variant_key}"
            save_icon(file_key, drawer_for_variant(variant_key, label))
            count += 1

    save_icon("lehenga-dry-clean", drawer_for_variant("lehenga-dry-clean", "Lehenga"))
    count += 1

    print(f"Generated {count} laundry variant icons from variation labels")


if __name__ == "__main__":
    main()
