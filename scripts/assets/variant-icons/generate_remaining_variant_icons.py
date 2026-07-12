#!/usr/bin/env python3
"""Generate variant icons for remaining categories (512x512, #1A233A)."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

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


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/Library/Fonts/Arial.ttf",
    ]
    for path in candidates:
        if Path(path).exists():
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def draw_inspection(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((206, 120, 306, 220), fill=COLOR)
    draw.polygon([(256, 220), (196, 360), (316, 360)], fill=COLOR)
    draw.ellipse((231, 145, 281, 195), fill="white")
    draw.rounded_rectangle((330, 150, 400, 280), radius=10, fill=COLOR)
    for y in (170, 195, 220):
        draw.rectangle((345, y, 385, y + 10), fill="white")
    draw.rounded_rectangle((130, 280, 190, 360), radius=8, fill=COLOR)
    draw.line((160, 300, 160, 340), fill="white", width=6)


def draw_basic_badge(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((96, 170, 416, 342), radius=28, outline=COLOR, width=14)
    f = font(72, bold=True)
    draw.text((256, 256), "BASIC", fill=COLOR, font=f, anchor="mm")


def draw_premium_badge(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((72, 150, 440, 362), radius=28, outline=COLOR, width=16)
    draw.polygon([(256, 118), (220, 170), (292, 170)], fill=COLOR)
    f = font(58, bold=True)
    draw.text((256, 262), "PREMIUM", fill=COLOR, font=f, anchor="mm")


def draw_sparkle(draw: ImageDraw.ImageDraw, x: int, y: int, r: int) -> None:
    draw.ellipse((x - r, y - r, x + r, y + r), fill=COLOR)
    draw.line((x, y - r - 10, x, y + r + 10), fill=COLOR, width=8)
    draw.line((x - r - 10, y, x + r + 10, y), fill=COLOR, width=8)


def draw_carpet(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((110, 250, 402, 380), radius=16, fill=COLOR)
    for x in range(130, 390, 36):
        draw.line((x, 250, x, 380), fill="white", width=3)
    draw.ellipse((180, 130, 332, 230), fill=COLOR)
    draw_sparkle(draw, 360, 150, 14)


def draw_home(draw: ImageDraw.ImageDraw) -> None:
    draw.polygon([(256, 100), (120, 230), (392, 230)], fill=COLOR)
    draw.rectangle((160, 230, 352, 390), fill=COLOR)
    draw.rectangle((220, 280, 292, 390), fill="white")


def draw_kitchen(draw: ImageDraw.ImageDraw) -> None:
    draw.rectangle((120, 160, 392, 390), outline=COLOR, width=16)
    draw.rectangle((150, 190, 250, 290), outline=COLOR, width=10)
    draw.rectangle((280, 190, 362, 290), outline=COLOR, width=10)
    draw.ellipse((200, 310, 312, 370), outline=COLOR, width=10)


def draw_tank(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((170, 120, 342, 360), radius=40, outline=COLOR, width=16)
    draw.rectangle((210, 90, 302, 130), fill=COLOR)
    draw.arc((200, 300, 312, 390), 0, 180, fill=COLOR, width=12)


def draw_garment(draw: ImageDraw.ImageDraw) -> None:
    draw.line((256, 110, 256, 170), fill=COLOR, width=12)
    draw.polygon([(256, 170), (170, 220), (170, 390), (342, 390), (342, 220)], outline=COLOR, width=14)
    draw.arc((190, 250, 322, 360), 0, 180, fill=COLOR, width=10)


def draw_wiring(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((140, 150, 372, 360), radius=12, outline=COLOR, width=14)
    for y in (200, 250, 300):
        draw.line((170, y, 342, y), fill=COLOR, width=8)
    draw.ellipse((190, 188, 214, 212), fill=COLOR)
    draw.ellipse((290, 238, 314, 262), fill=COLOR)


def draw_bulb(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((196, 120, 316, 240), outline=COLOR, width=14)
    draw.rectangle((226, 240, 286, 300), fill=COLOR)
    for i in range(5):
        draw.line((246 + i * 8, 300, 246 + i * 8, 330), fill=COLOR, width=4)
    draw.line((180, 180, 140, 140), fill=COLOR, width=10)
    draw.line((332, 180, 372, 140), fill=COLOR, width=10)


def draw_socket(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((170, 140, 342, 360), radius=20, outline=COLOR, width=16)
    draw.ellipse((220, 220, 250, 250), fill=COLOR)
    draw.ellipse((262, 220, 292, 250), fill=COLOR)
    draw.rectangle((236, 290, 276, 320), fill=COLOR)


def draw_washer(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((150, 150, 362, 360), radius=20, outline=COLOR, width=14)
    draw.ellipse((210, 210, 302, 302), outline=COLOR, width=10)
    draw.rectangle((230, 120, 282, 160), fill=COLOR)


def draw_iron(draw: ImageDraw.ImageDraw) -> None:
    draw.polygon([(256, 120), (360, 200), (152, 200)], fill=COLOR)
    draw.rectangle((220, 200, 292, 360), fill=COLOR)


def draw_curtain(draw: ImageDraw.ImageDraw) -> None:
    draw.rectangle((170, 120, 342, 390), fill=COLOR)
    for x in range(200, 330, 30):
        draw.line((x, 120, x, 390), fill="white", width=4)


def draw_bed(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((120, 220, 392, 360), radius=16, outline=COLOR, width=14)
    draw.rectangle((120, 180, 392, 240), fill=COLOR)


def draw_blanket(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((130, 180, 382, 360), radius=24, fill=COLOR)
    for y in range(220, 340, 30):
        draw.line((150, y, 362, y), fill="white", width=3)


PACKAGE_ICONS: dict[str, callable] = {
    "carpet-cleaning": draw_carpet,
    "full-home-cleaning": draw_home,
    "kitchen-cleaning": draw_kitchen,
    "tanky-cleaning": draw_tank,
    "lehenga-dry-clean": draw_garment,
    "suit-dry-clean": draw_garment,
    "shirt-dry-clean": draw_garment,
    "saree-dry-clean": draw_garment,
    "blazer-dry-clean": draw_garment,
    "woolen-dry-clean": draw_garment,
    "shawl-pashmina-dry-clean": draw_garment,
    "wash-and-iron": draw_washer,
    "iron-only": draw_iron,
    "curtain-cleaning": draw_curtain,
    "bedsheet-linen-cleaning": draw_bed,
    "blanket-cleaning": draw_blanket,
    "electrical-wiring": draw_wiring,
    "lighting-installation": draw_bulb,
    "switch-sockets": draw_socket,
}


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    for name, drawer in [
        ("book-site-inspection", draw_inspection),
        ("basic-package", draw_basic_badge),
        ("premium-package", draw_premium_badge),
    ]:
        img, draw = canvas()
        drawer(draw)
        save(name, img)
    for slug, drawer in PACKAGE_ICONS.items():
        img, draw = canvas()
        drawer(draw)
        save(slug, img)


if __name__ == "__main__":
    main()
