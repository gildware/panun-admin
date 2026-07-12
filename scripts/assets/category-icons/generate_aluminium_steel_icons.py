#!/usr/bin/env python3
"""Generate category icons for Aluminium & Steel Works."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw

COLOR = "#1A233A"
SIZE = 512
OUT = Path(__file__).resolve().parent


def canvas():
    img = Image.new("RGBA", (SIZE, SIZE), (255, 255, 255, 255))
    return img, ImageDraw.Draw(img)


def save(name: str, img: Image.Image) -> None:
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path.name}")


def aluminium_steel(draw: ImageDraw.ImageDraw) -> None:
    # I-beam + aluminium profile
    draw.rectangle((156, 140, 356, 180), fill=COLOR)
    draw.rectangle((236, 180, 276, 360), fill=COLOR)
    draw.rectangle((156, 360, 356, 400), fill=COLOR)
    draw.rounded_rectangle((120, 220, 150, 320), radius=6, fill=COLOR)
    draw.rounded_rectangle((362, 220, 392, 320), radius=6, fill=COLOR)


def installation(draw: ImageDraw.ImageDraw) -> None:
    # Wrench + panel
    draw.rectangle((140, 150, 220, 360), outline=COLOR, width=14)
    draw.line((180, 150, 180, 120), fill=COLOR, width=12)
    draw.ellipse((250, 200, 380, 330), outline=COLOR, width=14)
    draw.line((315, 140, 315, 200), fill=COLOR, width=12)
    draw.polygon([(300, 140), (330, 140), (345, 170), (285, 170)], fill=COLOR)


def repairs(draw: ImageDraw.ImageDraw) -> None:
    # Hammer + cracked panel
    draw.rectangle((130, 160, 230, 360), outline=COLOR, width=12)
    draw.line((180, 200, 210, 260), fill=COLOR, width=8)
    draw.line((210, 260, 170, 300), fill=COLOR, width=8)
    draw.rectangle((270, 180, 390, 340), outline=COLOR, width=12)
    draw.line((300, 210, 360, 280), fill=COLOR, width=10)
    draw.rectangle((300, 120, 340, 170), fill=COLOR)


def fabrication(draw: ImageDraw.ImageDraw) -> None:
    # Welding spark + metal frame
    draw.rectangle((140, 200, 360, 360), outline=COLOR, width=14)
    draw.line((140, 280, 360, 280), fill=COLOR, width=10)
    draw.line((250, 200, 250, 360), fill=COLOR, width=10)
    draw.polygon([(300, 120), (330, 150), (360, 120), (345, 170), (315, 170)], fill=COLOR)


ICONS = {
    "aluminium-steel-works": aluminium_steel,
    "metal-works-installation": installation,
    "metal-works-repairs": repairs,
    "metal-works-fabrication": fabrication,
}


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    for name, drawer in ICONS.items():
        img, draw = canvas()
        drawer(draw)
        save(name, img)


if __name__ == "__main__":
    main()
