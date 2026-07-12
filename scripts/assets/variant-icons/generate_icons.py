#!/usr/bin/env python3
"""Generate consistent navy-blue service variant icons (512x512 PNG)."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw

COLOR = "#1A233A"
SIZE = 512
OUT = Path(__file__).resolve().parent


def canvas() -> tuple[Image.Image, ImageDraw.ImageDraw]:
    img = Image.new("RGBA", (SIZE, SIZE), (255, 255, 255, 255))
    return img, ImageDraw.Draw(img)


def save(name: str, img: Image.Image) -> None:
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def location_pin(draw: ImageDraw.ImageDraw) -> None:
    # Map pin + small clipboard for site inspection
    draw.ellipse((206, 120, 306, 220), fill=COLOR)
    draw.polygon([(256, 220), (196, 360), (316, 360)], fill=COLOR)
    draw.ellipse((231, 145, 281, 195), fill="white")
    draw.rounded_rectangle((330, 150, 400, 280), radius=10, fill=COLOR)
    draw.rectangle((345, 170, 385, 180), fill="white")
    draw.rectangle((345, 195, 385, 205), fill="white")
    draw.rectangle((345, 220, 370, 230), fill="white")
    draw.rectangle((130, 280, 190, 360), fill=COLOR)
    draw.line((160, 300, 160, 340), fill="white", width=6)
    for y in (310, 325, 340):
        draw.line((145, y, 175, y), fill="white", width=4)


def door_install(draw: ImageDraw.ImageDraw) -> None:
    # Door frame + handle
    draw.rounded_rectangle((150, 110, 362, 390), radius=8, outline=COLOR, width=18)
    draw.rectangle((170, 130, 342, 370), fill=COLOR)
    draw.ellipse((300, 230, 330, 260), fill="white")
    draw.rectangle((256, 130, 256, 370), fill="white", width=4)


ICON_DRAWERS: dict[str, callable] = {
    "book-site-inspection": location_pin,
    "door-installation": door_install,
}


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    for slug, drawer in ICON_DRAWERS.items():
        img, draw = canvas()
        drawer(draw)
        save(slug, img)


if __name__ == "__main__":
    main()
