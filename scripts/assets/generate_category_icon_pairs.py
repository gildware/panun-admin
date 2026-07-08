#!/usr/bin/env python3
"""Generate paired light/dark category icons (same shape, color only differs)."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw

SIZE = 512
LIGHT = "#25274D"
DARK = "#FFFFFF"
ROOT = Path(__file__).resolve().parent.parent / "category-icons"
LIGHT_DIR = ROOT / "light"
DARK_DIR = ROOT / "dark"


def canvas() -> tuple[Image.Image, ImageDraw.ImageDraw]:
    return Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0)), ImageDraw.Draw(Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0)))


def new_canvas() -> tuple[Image.Image, ImageDraw.ImageDraw]:
    img = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    return img, ImageDraw.Draw(img)


def save_pair(slug: str, drawer) -> None:
    for mode, color, out_dir in (("light", LIGHT, LIGHT_DIR), ("dark", DARK, DARK_DIR)):
        img, draw = new_canvas()
        drawer(draw, color)
        out_dir.mkdir(parents=True, exist_ok=True)
        img.save(out_dir / f"{slug}.png", "PNG", optimize=True)
    print(f"  {slug}")


def plus_sign(draw: ImageDraw.ImageDraw, cx: int, cy: int, size: int, color: str, width: int = 14) -> None:
    draw.line((cx - size, cy, cx + size, cy), fill=color, width=width)
    draw.line((cx, cy - size, cx, cy + size), fill=color, width=width)


def crack(draw: ImageDraw.ImageDraw, x1: int, y1: int, x2: int, y2: int, color: str, width: int = 10) -> None:
    draw.line((x1, y1, x2, y2), fill=color, width=width)
    draw.line((x2, y2, x2 - 18, y2 + 22), fill=color, width=width)


# --- Main categories ---

def carpentary(draw, c):
    draw.polygon([(170, 130), (342, 130), (360, 175), (152, 175)], fill=c)
    draw.rectangle((210, 175, 302, 195), fill=c)
    draw.rectangle((230, 195, 282, 360), fill=c)
    draw.rectangle((150, 250, 360, 270), fill=c)
    draw.rectangle((300, 220, 360, 240), fill=c)
    draw.polygon([(360, 240), (410, 210), (420, 225), (370, 255)], fill=c)


def cleaning(draw, c):
    draw.rectangle((220, 130, 292, 170), fill=c)
    draw.rectangle((248, 170, 264, 360), fill=c)
    draw.ellipse((170, 330, 340, 390), fill=c)
    draw.rectangle((170, 300, 340, 340), fill=c)
    draw.rectangle((330, 180, 360, 320), fill=c)


def laundry(draw, c):
    draw.arc((206, 110, 306, 180), 0, 180, fill=c, width=14)
    draw.line((256, 110, 256, 150), fill=c, width=12)
    draw.polygon([(170, 180), (342, 180), (310, 390), (202, 390)], fill=c)


def electrical(draw, c):
    draw.rounded_rectangle((190, 150, 322, 280), radius=20, fill=c)
    draw.rectangle((220, 280, 250, 360), fill=c)
    draw.rectangle((262, 280, 292, 360), fill=c)
    draw.polygon([(360, 150), (390, 150), (350, 230), (380, 230), (320, 330), (340, 250), (310, 250)], fill=c)


def home_appliance(draw, c):
    draw.rounded_rectangle((120, 110, 230, 390), radius=12, fill=c)
    draw.rounded_rectangle((250, 180, 390, 390), radius=12, fill=c)
    draw.ellipse((285, 230, 355, 300), outline=c, width=12)
    draw.rounded_rectangle((300, 110, 390, 170), radius=8, fill=c)


def masonry(draw, c):
    for row, y in enumerate(range(170, 360, 45)):
        offset = 25 if row % 2 else 0
        for x in range(130 + offset, 360, 70):
            draw.rounded_rectangle((x, y, x + 60, y + 35), radius=4, fill=c)
    draw.polygon([(330, 120), (400, 150), (360, 170), (300, 140)], fill=c)


def mens_salon(draw, c):
    draw.ellipse((140, 150, 280, 330), fill=c)
    draw.polygon([(300, 140), (360, 200), (330, 220), (280, 170)], fill=c)
    draw.polygon([(300, 340), (360, 280), (330, 260), (280, 310)], fill=c)


def womens_salon(draw, c):
    draw.ellipse((150, 150, 290, 330), fill=c)
    draw.arc((120, 160, 220, 360), 30, 150, fill=c, width=18)
    draw.polygon([(310, 140), (370, 200), (340, 220), (290, 170)], fill=c)
    draw.polygon([(310, 340), (370, 280), (340, 260), (290, 310)], fill=c)


def painting(draw, c):
    draw.rectangle((240, 120, 272, 300), fill=c)
    draw.rounded_rectangle((200, 300, 312, 360), radius=10, fill=c)
    draw.rectangle((320, 220, 390, 360), fill=c)


def plumbing(draw, c):
    draw.rectangle((230, 120, 282, 180), fill=c)
    draw.rectangle((248, 180, 264, 260), fill=c)
    draw.arc((200, 240, 312, 340), 0, 180, fill=c, width=16)
    draw.ellipse((246, 350, 266, 380), fill=c)


# --- Install / repair helpers ---

def install_arrow(draw, c):
    plus_sign(draw, 420, 120, 28, c)


def install_shelf(draw, c):
    draw.rectangle((120, 200, 380, 220), fill=c)
    draw.rectangle((120, 280, 380, 300), fill=c)
    draw.polygon([(400, 240), (460, 240), (430, 270)], fill=c)
    install_arrow(draw, c)


def repair_crack_plank(draw, c):
    draw.rounded_rectangle((120, 180, 392, 332), radius=8, fill=c)
    crack(draw, 200, 200, 280, 280, c)
    draw.line((300, 260, 340, 300), fill=c, width=10)


# --- Subcategory drawers ---

def carpentry_installation(draw, c):
    install_shelf(draw, c)
    draw.rounded_rectangle((140, 140, 300, 190), radius=6, fill=c)


def carpentry_repairs(draw, c):
    repair_crack_plank(draw, c)
    draw.polygon([(330, 150), (390, 180), (360, 210), (310, 180)], fill=c)


def home_cleaning(draw, c):
    draw.rectangle((330, 150, 360, 300), fill=c)
    draw.ellipse((345, 130, 385, 160), fill=c)
    draw.ellipse((170, 320, 340, 390), fill=c)
    draw.rectangle((230, 170, 264, 360), fill=c)


def office_cleaning(draw, c):
    draw.rectangle((150, 160, 362, 360), outline=c, width=12)
    draw.rectangle((190, 200, 250, 280), outline=c, width=8)
    draw.rectangle((290, 200, 350, 280), outline=c, width=8)
    draw.rectangle((248, 170, 264, 360), fill=c)


def post_construction_cleaning(draw, c):
    draw.polygon([(256, 120), (380, 360), (132, 360)], outline=c, width=12)
    draw.rectangle((220, 260, 292, 340), fill=c)


def garment_care(draw, c):
    draw.arc((206, 110, 306, 180), 0, 180, fill=c, width=14)
    draw.polygon([(190, 180), (322, 180), (292, 360), (220, 360)], fill=c)


def fabric_care(draw, c):
    garment_care(draw, c)
    draw.ellipse((330, 250, 390, 310), outline=c, width=10)


def premium_fabric(draw, c):
    fabric_care(draw, c)
    draw.polygon([(350, 140), (390, 170), (370, 190), (330, 160)], fill=c)


def electricity_installation(draw, c):
    electrical(draw, c)
    install_arrow(draw, c)


def electricity_repair(draw, c):
    draw.rounded_rectangle((190, 150, 322, 280), radius=20, fill=c)
    crack(draw, 230, 180, 290, 250, c)
    draw.polygon([(360, 150), (390, 180), (360, 210), (330, 180)], fill=c)


def ac(draw, c):
    draw.rounded_rectangle((110, 170, 402, 300), radius=16, fill=c)
    for x in range(140, 380, 35):
        draw.line((x, 210, x, 270), fill=c, width=6)
    draw.rectangle((220, 300, 292, 360), fill=c)


def battery(draw, c):
    draw.rounded_rectangle((150, 160, 362, 340), radius=16, fill=c)
    draw.rectangle((220, 130, 292, 160), fill=c)
    draw.polygon([(360, 200), (410, 230), (370, 250), (330, 220)], fill=c)


def cctv(draw, c):
    draw.rounded_rectangle((140, 200, 300, 280), radius=20, fill=c)
    draw.ellipse((170, 225, 220, 255), outline=c, width=8)
    draw.polygon([(300, 240), (390, 210), (390, 270)], fill=c)


def geyser(draw, c):
    draw.rounded_rectangle((220, 110, 292, 360), radius=30, fill=c)
    draw.ellipse((235, 140, 277, 182), outline=c, width=8)


def tv(draw, c):
    draw.rounded_rectangle((110, 140, 402, 310), radius=12, fill=c)
    draw.rectangle((220, 310, 292, 360), fill=c)


def refrigerator(draw, c):
    draw.rounded_rectangle((170, 110, 342, 390), radius=14, fill=c)
    draw.line((256, 130, 256, 370), fill=c, width=6)


def washing_machine(draw, c):
    draw.rounded_rectangle((150, 120, 362, 390), radius=16, fill=c)
    draw.ellipse((210, 200, 302, 292), outline=c, width=12)


def small_appliance(draw, c):
    draw.ellipse((200, 280, 312, 360), fill=c)
    draw.rectangle((230, 160, 282, 280), fill=c)


def water_purifier(draw, c):
    draw.rounded_rectangle((210, 120, 302, 360), radius=20, fill=c)
    draw.ellipse((230, 150, 282, 190), outline=c, width=8)
    draw.line((256, 190, 256, 320), fill=c, width=8)


def masonry_install(draw, c):
    masonry(draw, c)
    install_arrow(draw, c)


def masonry_repair(draw, c):
    for row, y in enumerate(range(170, 360, 45)):
        offset = 25 if row % 2 else 0
        for x in range(130 + offset, 360, 70):
            draw.rounded_rectangle((x, y, x + 60, y + 35), radius=4, fill=c)
    crack(draw, 250, 200, 310, 280, c)


def beard_shaving(draw, c):
    mens_salon(draw, c)
    draw.ellipse((190, 250, 240, 300), outline=c, width=8)


def mens_hair(draw, c):
    mens_salon(draw, c)
    draw.arc((160, 140, 260, 220), 200, 340, fill=c, width=12)


def mens_skin(draw, c):
    draw.ellipse((180, 160, 332, 340), fill=c)
    draw.ellipse((230, 250, 282, 300), outline=c, width=8)


def exterior_painting(draw, c):
    draw.rectangle((120, 120, 180, 390), fill=c)
    draw.rectangle((332, 120, 392, 390), fill=c)
    draw.rectangle((240, 120, 272, 300), fill=c)
    draw.rounded_rectangle((200, 300, 312, 360), radius=10, fill=c)


def interior_painting(draw, c):
    painting(draw, c)
    draw.rectangle((120, 160, 160, 360), outline=c, width=8)


def plumbing_fixtures(draw, c):
    plumbing(draw, c)
    draw.ellipse((300, 300, 360, 360), fill=c)


def plumbing_installs(draw, c):
    plumbing(draw, c)
    install_arrow(draw, c)


def beauty_grooming(draw, c):
    womens_salon(draw, c)
    draw.ellipse((210, 220, 250, 260), outline=c, width=8)


def hair_care(draw, c):
    womens_salon(draw, c)
    draw.arc((130, 150, 230, 340), 30, 150, fill=c, width=16)


def skin_facial(draw, c):
    draw.ellipse((180, 160, 332, 340), fill=c)
    draw.ellipse((220, 210, 292, 280), outline=c, width=10)


ICON_DRAWERS: dict[str, callable] = {
    "carpentary": carpentary,
    "cleaning": cleaning,
    "laundry": laundry,
    "electrical": electrical,
    "home-appliance": home_appliance,
    "masonry": masonry,
    "mens-salon": mens_salon,
    "womens-salon": womens_salon,
    "painting": painting,
    "plumbing": plumbing,
    "carpentry-installation": carpentry_installation,
    "carpentry-repairs": carpentry_repairs,
    "home-cleaning": home_cleaning,
    "office-commercial-cleaning": office_cleaning,
    "post-construction-cleaning": post_construction_cleaning,
    "garment-care": garment_care,
    "household-fabric-care": fabric_care,
    "premium-fabric-care": premium_fabric,
    "installation-services": electricity_installation,
    "repairing-services": electricity_repair,
    "air-conditioners": ac,
    "battery-inverters": battery,
    "cctv": cctv,
    "geysers": geyser,
    "led-smart-tv": tv,
    "refrigerators": refrigerator,
    "induction-heaters": small_appliance,
    "washing-machine": washing_machine,
    "ro-purifier": water_purifier,
    "masonry-installs": masonry_install,
    "masonry-repairs": masonry_repair,
    "mens-beard-shaving": beard_shaving,
    "mens-hair-services": mens_hair,
    "mens-skin-grooming-care": mens_skin,
    "exterior-painting": exterior_painting,
    "interior-painting": interior_painting,
    "plumbing-fixtures": plumbing_fixtures,
    "plumbing-installs": plumbing_installs,
    "beauty-grooming": beauty_grooming,
    "hair-care-services": hair_care,
    "skin-facial-care": skin_facial,
}


def main() -> None:
    print(f"Generating {len(ICON_DRAWERS)} icon pairs...")
    for slug, drawer in ICON_DRAWERS.items():
        save_pair(slug, drawer)
    print(f"Done -> {LIGHT_DIR} and {DARK_DIR}")


if __name__ == "__main__":
    main()
