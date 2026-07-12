#!/usr/bin/env python3
"""Generate consistent navy-blue category icons (512x512 PNG)."""

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
    print(f"Wrote {path.name}")


def carpentry(draw: ImageDraw.ImageDraw) -> None:
    # Hard hat + hammer + saw
    draw.polygon([(180, 120), (332, 120), (350, 165), (162, 165)], fill=COLOR)
    draw.rectangle((205, 165, 307, 185), fill=COLOR)
    draw.rectangle((220, 185, 292, 320), fill=COLOR)
    draw.rectangle((150, 250, 360, 270), fill=COLOR)
    draw.rectangle((300, 210, 360, 230), fill=COLOR)
    draw.polygon([(360, 230), (410, 200), (420, 215), (370, 245)], fill=COLOR)
    draw.rectangle((130, 300, 170, 380), fill=COLOR)
    draw.polygon([(170, 300), (210, 300), (190, 390), (150, 390)], fill=COLOR)


def cleaning(draw: ImageDraw.ImageDraw) -> None:
    # Mop + bucket + spray bottle
    draw.rectangle((220, 130, 292, 170), fill=COLOR)
    draw.rectangle((248, 170, 264, 360), fill=COLOR)
    draw.ellipse((170, 330, 340, 390), fill=COLOR)
    draw.rectangle((170, 300, 340, 340), fill=COLOR)
    draw.rectangle((330, 180, 360, 320), fill=COLOR)
    draw.rectangle((350, 150, 380, 190), fill=COLOR)
    draw.ellipse((345, 130, 385, 160), fill=COLOR)


def laundry(draw: ImageDraw.ImageDraw) -> None:
    # Hanger + shirt
    draw.arc((206, 110, 306, 180), 0, 180, fill=COLOR, width=14)
    draw.line((256, 110, 256, 150), fill=COLOR, width=12)
    draw.polygon([(170, 180), (342, 180), (310, 390), (202, 390)], fill=COLOR)
    draw.rectangle((230, 220, 282, 300), fill="white")


def electrician(draw: ImageDraw.ImageDraw) -> None:
    # Plug + lightning bolt
    draw.rounded_rectangle((190, 150, 322, 280), radius=20, fill=COLOR)
    draw.rectangle((220, 280, 250, 360), fill=COLOR)
    draw.rectangle((262, 280, 292, 360), fill=COLOR)
    draw.polygon([(360, 150), (390, 150), (350, 230), (380, 230), (320, 330), (340, 250), (310, 250)], fill=COLOR)


def appliances(draw: ImageDraw.ImageDraw) -> None:
    # Fridge + washer
    draw.rounded_rectangle((120, 110, 230, 390), radius=12, fill=COLOR)
    draw.line((175, 130, 175, 370), fill="white", width=6)
    draw.rounded_rectangle((250, 180, 390, 390), radius=12, fill=COLOR)
    draw.ellipse((285, 230, 355, 300), outline="white", width=10)
    draw.rounded_rectangle((300, 110, 390, 170), radius=8, fill=COLOR)


def masonry(draw: ImageDraw.ImageDraw) -> None:
    # Brick wall + trowel
    for row, y in enumerate(range(170, 360, 45)):
        offset = 25 if row % 2 else 0
        for x in range(130 + offset, 360, 70):
            draw.rounded_rectangle((x, y, x + 60, y + 35), radius=4, fill=COLOR)
    draw.polygon([(330, 120), (400, 150), (360, 170), (300, 140)], fill=COLOR)
    draw.rectangle((280, 140, 310, 200), fill=COLOR)


def mens_salon(draw: ImageDraw.ImageDraw) -> None:
    # Male head profile + scissors
    draw.ellipse((140, 150, 280, 330), fill=COLOR)
    draw.ellipse((190, 200, 250, 260), fill="white")
    draw.polygon([(300, 140), (360, 200), (330, 220), (280, 170)], fill=COLOR)
    draw.polygon([(300, 340), (360, 280), (330, 260), (280, 310)], fill=COLOR)
    draw.ellipse((305, 195, 325, 215), fill="white")


def womens_salon(draw: ImageDraw.ImageDraw) -> None:
    # Female head profile + scissors
    draw.ellipse((150, 150, 290, 330), fill=COLOR)
    draw.arc((120, 160, 220, 360), 30, 150, fill=COLOR, width=18)
    draw.ellipse((200, 200, 255, 255), fill="white")
    draw.polygon([(310, 140), (370, 200), (340, 220), (290, 170)], fill=COLOR)
    draw.polygon([(310, 340), (370, 280), (340, 260), (290, 310)], fill=COLOR)


def painting(draw: ImageDraw.ImageDraw) -> None:
    # Paint roller + bucket
    draw.rectangle((240, 120, 272, 300), fill=COLOR)
    draw.rounded_rectangle((200, 300, 312, 360), radius=10, fill=COLOR)
    draw.rectangle((320, 220, 390, 360), fill=COLOR)
    draw.rectangle((300, 200, 410, 230), fill=COLOR)
    draw.ellipse((150, 250, 220, 320), fill=COLOR)


def plumbing(draw: ImageDraw.ImageDraw) -> None:
    # Faucet + pipe + drop
    draw.rectangle((230, 120, 282, 180), fill=COLOR)
    draw.rectangle((248, 180, 264, 260), fill=COLOR)
    draw.arc((200, 240, 312, 340), 0, 180, fill=COLOR, width=16)
    draw.rectangle((120, 300, 392, 330), fill=COLOR)
    draw.ellipse((246, 350, 266, 380), fill=COLOR)


def ac(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((110, 170, 402, 300), radius=16, fill=COLOR)
    for x in range(140, 380, 35):
        draw.line((x, 210, x, 270), fill="white", width=6)
    draw.rectangle((220, 300, 292, 360), fill=COLOR)
    draw.ellipse((330, 120, 390, 180), fill=COLOR)
    draw.line((360, 180, 360, 220), fill=COLOR, width=10)


def battery(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((150, 160, 362, 340), radius=16, fill=COLOR)
    draw.rectangle((220, 130, 292, 160), fill=COLOR)
    draw.rectangle((190, 210, 230, 290), fill="white")
    draw.rectangle((260, 210, 300, 290), fill="white")
    draw.polygon([(340, 200), (390, 230), (370, 250), (330, 220)], fill=COLOR)


def tv(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((110, 140, 402, 310), radius=12, fill=COLOR)
    draw.rounded_rectangle((140, 170, 372, 280), radius=8, fill="white")
    draw.rectangle((220, 310, 292, 360), fill=COLOR)
    draw.rectangle((170, 360, 342, 380), fill=COLOR)


def refrigerator(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((170, 110, 342, 390), radius=14, fill=COLOR)
    draw.line((256, 130, 256, 370), fill="white", width=6)
    draw.rectangle((190, 150, 240, 170), fill="white")
    draw.rectangle((270, 250, 320, 270), fill="white")


def washer(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((150, 120, 362, 390), radius=16, fill=COLOR)
    draw.ellipse((210, 200, 302, 292), outline="white", width=12)
    draw.rectangle((180, 140, 332, 180), fill="white")


def geyser(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((220, 110, 292, 360), radius=30, fill=COLOR)
    draw.ellipse((235, 140, 277, 182), outline="white", width=8)
    draw.rectangle((200, 360, 312, 390), fill=COLOR)


def generic_tool(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((186, 186, 326, 326), outline=COLOR, width=18)
    draw.rectangle((246, 120, 266, 200), fill=COLOR)
    draw.polygon([(236, 310), (276, 310), (286, 390), (226, 390)], fill=COLOR)


def _draw_bug(draw: ImageDraw.ImageDraw, cx: int, cy: int, scale: float = 1.0) -> None:
    s = scale
    draw.ellipse((cx - int(36 * s), cy - int(30 * s), cx + int(36 * s), cy + int(30 * s)), fill=COLOR)
    draw.line((cx - int(58 * s), cy - int(6 * s), cx - int(82 * s), cy + int(28 * s)), fill=COLOR, width=int(8 * s))
    draw.line((cx + int(58 * s), cy - int(6 * s), cx + int(82 * s), cy + int(28 * s)), fill=COLOR, width=int(8 * s))
    draw.line((cx - int(20 * s), cy + int(24 * s), cx - int(20 * s), cy + int(58 * s)), fill=COLOR, width=int(8 * s))
    draw.line((cx, cy + int(24 * s), cx, cy + int(58 * s)), fill=COLOR, width=int(8 * s))
    draw.line((cx + int(20 * s), cy + int(24 * s), cx + int(20 * s), cy + int(58 * s)), fill=COLOR, width=int(8 * s))


def _draw_shield(draw: ImageDraw.ImageDraw, cx: int = 256, top: int = 90) -> None:
    draw.polygon(
        [(cx, top), (cx - 106, top + 50), (cx - 106, top + 190), (cx, top + 310), (cx + 106, top + 190), (cx + 106, top + 50)],
        outline=COLOR,
        width=14,
    )


def pest_control(draw: ImageDraw.ImageDraw) -> None:
    _draw_shield(draw)
    _draw_bug(draw, 256, 230)


def home_pest_control(draw: ImageDraw.ImageDraw) -> None:
    draw.polygon([(256, 118), (150, 188), (150, 330), (362, 330), (362, 188)], outline=COLOR, width=14)
    draw.polygon([(256, 88), (130, 188), (382, 188)], outline=COLOR, width=14)
    draw.rectangle((220, 250, 292, 330), fill=COLOR)
    _draw_bug(draw, 256, 210, 0.72)


def office_pest_control(draw: ImageDraw.ImageDraw) -> None:
    draw.rectangle((130, 120, 382, 360), outline=COLOR, width=14)
    for row in range(4):
        y = 150 + row * 52
        draw.line((130, y, 382, y), fill=COLOR, width=6)
        for col in range(3):
            x = 158 + col * 68
            draw.rectangle((x, y + 10, x + 44, y + 36), fill=COLOR if (row + col) % 2 else "white", outline=COLOR, width=4)
    _draw_bug(draw, 256, 286, 0.62)


def restaurant_pest_control(draw: ImageDraw.ImageDraw) -> None:
    draw.rectangle((150, 170, 362, 360), outline=COLOR, width=14)
    draw.polygon([(256, 108), (150, 170), (362, 170)], outline=COLOR, width=14)
    draw.line((150, 250, 362, 250), fill=COLOR, width=10)
    draw.ellipse((210, 286, 250, 326), outline=COLOR, width=8)
    draw.ellipse((272, 286, 312, 326), outline=COLOR, width=8)
    _draw_bug(draw, 256, 318, 0.55)


ICON_DRAWERS: dict[str, callable] = {
    "carpentary": carpentry,
    "carpentry-installation": carpentry,
    "carpentry-repairs": carpentry,
    "cleaning": cleaning,
    "home-cleaning": cleaning,
    "office-commercial-cleaning": cleaning,
    "post-construction-cleaning": cleaning,
    "tamkey-cleaning": cleaning,
    "laundry": laundry,
    "garment-care": laundry,
    "household-fabric-care": laundry,
    "premium-fabric-care": laundry,
    "electrical": electrician,
    "installation-services": electrician,
    "repairing-services": electrician,
    "home-appliance": appliances,
    "air-conditioners": ac,
    "battery-inverters": battery,
    "led-smart-tv": tv,
    "refrigerators": refrigerator,
    "washing-machine": washer,
    "geyser-service": geyser,
    "geysers": geyser,
    "masonry": masonry,
    "masonry-installs": masonry,
    "masonry-repairs": masonry,
    "mens-salon": mens_salon,
    "mens-beard-shaving": mens_salon,
    "mens-hair-services": mens_salon,
    "mens-skin-grooming-care": mens_salon,
    "womens-salon": womens_salon,
    "beauty-grooming": womens_salon,
    "hair-care-services": womens_salon,
    "skin-facial-care": womens_salon,
    "painting": painting,
    "exterior-painting": painting,
    "interior-painting": painting,
    "plumbing": plumbing,
    "plumbing-fixtures": plumbing,
    "plumbing-installs": plumbing,
    "pest-control": pest_control,
    "home-pest-control": home_pest_control,
    "office-pest-control": office_pest_control,
    "restaurant-pest-control": restaurant_pest_control,
}

PEST_CONTROL_SLUGS = (
    "pest-control",
    "home-pest-control",
    "office-pest-control",
    "restaurant-pest-control",
)


def main() -> None:
    import argparse

    parser = argparse.ArgumentParser(description="Generate navy-blue category icons (512x512 PNG).")
    parser.add_argument("--only", nargs="*", help="Generate only these slugs.")
    parser.add_argument("--force", action="store_true", help="Overwrite existing icons.")
    args = parser.parse_args()

    OUT.mkdir(parents=True, exist_ok=True)
    targets = args.only if args.only else list(ICON_DRAWERS.keys())

    for slug in targets:
        drawer = ICON_DRAWERS.get(slug)
        if drawer is None:
            print(f"Skip unknown slug: {slug}")
            continue
        path = OUT / f"{slug}.png"
        if path.exists() and not args.force:
            continue
        img, draw = canvas()
        drawer(draw)
        save(slug, img)


if __name__ == "__main__":
    main()
