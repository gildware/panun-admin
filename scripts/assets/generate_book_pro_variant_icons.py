#!/usr/bin/env python3
"""Variant icons for Book Painter / Mason / Carpenter hourly packages."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

COLOR = "#1A233A"
SIZE = 512
SCALE = 2
OUT = Path(__file__).resolve().parent / "variant-icons"

SERVICES = ["book-a-carpenter", "book-a-painter", "book-a-mason"]
VARIANTS = [
    ("book-for-hour", "1 hr"),
    ("book-for-4-hours", "4 hr"),
    ("book-for-full-day", "Full day"),
]


def s(v: int) -> int:
    return v * SCALE


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/Library/Fonts/Arial Bold.ttf" if bold else "/Library/Fonts/Arial.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    ]
    for path in candidates:
        if Path(path).exists():
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def draw_clock(draw: ImageDraw.ImageDraw, label: str, full_day: bool = False) -> None:
    cx, cy = s(256), s(220)
    radius = s(120)
    stroke = s(14)
    draw.ellipse((cx - radius, cy - radius, cx + radius, cy + radius), outline=COLOR, width=stroke)
    draw.ellipse((cx - s(8), cy - radius - s(6), cx + s(8), cy - radius + s(18)), fill=COLOR)

    if full_day:
        sun_r = s(36)
        sx, sy = cx, cy - s(10)
        draw.ellipse((sx - sun_r, sy - sun_r, sx + sun_r, sy + sun_r), outline=COLOR, width=stroke)
        for angle in range(0, 360, 45):
            import math

            rad = math.radians(angle)
            x1 = sx + int(math.cos(rad) * sun_r * 1.25)
            y1 = sy + int(math.sin(rad) * sun_r * 1.25)
            x2 = sx + int(math.cos(rad) * sun_r * 1.55)
            y2 = sy + int(math.sin(rad) * sun_r * 1.55)
            draw.line((x1, y1, x2, y2), fill=COLOR, width=s(6))
    else:
        draw.line((cx, cy, cx, cy - s(70)), fill=COLOR, width=stroke)
        if label.startswith("4"):
            draw.line((cx, cy, cx + s(55), cy + s(20)), fill=COLOR, width=stroke)
        else:
            draw.line((cx, cy, cx + s(40), cy), fill=COLOR, width=stroke)

    badge_font = font(s(42), bold=True)
    bbox = draw.textbbox((0, 0), label, font=badge_font)
    tw = bbox[2] - bbox[0]
    draw.text((cx - tw // 2, s(360)), label, fill=COLOR, font=badge_font)


def save_icon(path: Path, label: str, full_day: bool = False) -> None:
    big = SIZE * SCALE
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    draw_clock(ImageDraw.Draw(img), label, full_day=full_day)
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    path.parent.mkdir(parents=True, exist_ok=True)
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def main() -> None:
    for slug in SERVICES:
        for variant_key, label in VARIANTS:
            out = OUT / f"{slug}-{variant_key}.png"
            save_icon(out, label, full_day=variant_key == "book-for-full-day")


if __name__ == "__main__":
    main()
