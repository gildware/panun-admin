#!/usr/bin/env python3
"""Generate painting service images + slug-specific book-site-inspection variant icons."""

from __future__ import annotations

import importlib.util
import json
import math
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

SCRIPTS = Path(__file__).resolve().parents[1]
CATALOG = SCRIPTS / "data" / "painting-catalog.php"
SERVICE_IMG = Path(__file__).resolve().parent / "service-images"
VARIANT_IMG = Path(__file__).resolve().parent / "variant-icons"
REPAIR_ICONS = VARIANT_IMG / "generate_repair_variant_icons.py"
SIZE = 512
SCALE = 2

PALETTES = [
    ("#1E3A5F", "#3B82F6", "#EFF6FF"),
    ("#7C2D12", "#F97316", "#FFF7ED"),
    ("#14532D", "#22C55E", "#F0FDF4"),
    ("#581C87", "#A855F7", "#FAF5FF"),
    ("#0F766E", "#14B8A6", "#F0FDFA"),
    ("#9A3412", "#FB923C", "#FFF7ED"),
    ("#1E40AF", "#60A5FA", "#EFF6FF"),
    ("#854D0E", "#EAB308", "#FEFCE8"),
]


def load_catalog() -> dict:
    cmd = ["php", "-r", f"echo json_encode(require '{CATALOG}');"]
    result = subprocess.run(cmd, capture_output=True, text=True, check=True)
    return json.loads(result.stdout)


def load_repair_icons():
    spec = importlib.util.spec_from_file_location("repair_icons", REPAIR_ICONS)
    mod = importlib.util.module_from_spec(spec)
    assert spec.loader
    spec.loader.exec_module(mod)
    return mod


def _font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/Library/Fonts/Arial.ttf",
    ]
    for path in candidates:
        if Path(path).exists():
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def _gradient(size: tuple[int, int], c1: str, c2: str) -> Image.Image:
    base = Image.new("RGB", size, c1)
    top = Image.new("RGB", size, c2)
    mask = Image.linear_gradient("L").resize(size)
    return Image.composite(top, base, mask)


def _wrap_title(name: str) -> str:
    words = name.split()
    if len(words) <= 2:
        return name
    mid = len(words) // 2
    return " ".join(words[:mid]) + "\n" + " ".join(words[mid:])


def _subtitle(slug: str, sub: str) -> str:
    if sub == "exterior-painting":
        return "Exterior painting · site inspection first"
    if "touch-up" in slug or "patch" in slug:
        return "Quick fixes · neat finish"
    if "consultation" in slug:
        return "Estimate visit · scope planning"
    if "waterproof" in slug or "weather" in slug:
        return "Weather shield · moisture protection"
    if "putty" in slug or "crack" in slug or "stain" in slug:
        return "Surface prep · repair before paint"
    if "primer" in slug:
        return "Base coat · better adhesion"
    return "Interior painting · site inspection first"


def _draw_service_icon(draw: ImageDraw.ImageDraw, slug: str, box: tuple[int, int, int, int], accent: str) -> None:
    x1, y1, x2, y2 = box
    w, h = x2 - x1, y2 - y1
    cx, cy = (x1 + x2) // 2, (y1 + y2) // 2
    stroke = max(4, w // 36)
    c = accent

    if any(k in slug for k in ("building", "full-house-exterior", "exterior-wall")):
        draw.rectangle((x1 + w // 8, y1 + h // 4, x2 - w // 8, y2 - h // 8), outline=c, width=stroke)
        for row in range(2):
            for col in range(2):
                wx = x1 + w // 6 + col * (w // 4)
                wy = y1 + h // 3 + row * (h // 5)
                draw.rectangle((wx, wy, wx + w // 12, wy + h // 10), fill=c)
    elif "boundary" in slug:
        draw.rectangle((x1 + w // 10, cy - h // 12, x2 - w // 10, cy + h // 12), outline=c, width=stroke)
        for px in range(x1 + w // 8, x2 - w // 8, w // 8):
            draw.line((px, cy - h // 10, px, cy + h // 10), fill=c, width=stroke // 2)
    elif "texture" in slug:
        for i, px in enumerate(range(x1 + w // 10, x2 - w // 10, w // 7)):
            draw.rectangle((px, y1 + h // 5, px + w // 12, y2 - h // 6), outline=c, width=stroke // 2)
            if i % 2:
                draw.line((px, y1 + h // 4, px + w // 12, y2 - h // 5), fill=c, width=stroke // 2)
    elif "door" in slug or "gate" in slug:
        draw.rounded_rectangle((x1 + w // 5, y1 + h // 6, x2 - w // 5, y2 - h // 8), radius=12, outline=c, width=stroke)
        draw.ellipse((x2 - w // 4, cy - h // 16, x2 - w // 6, cy + h // 16), fill=c)
    elif "window" in slug or "grille" in slug:
        draw.rectangle((x1 + w // 6, y1 + h // 6, x2 - w // 6, y2 - h // 6), outline=c, width=stroke)
        draw.line((cx, y1 + h // 6, cx, y2 - h // 6), fill=c, width=stroke)
        draw.line((x1 + w // 6, cy, x2 - w // 6, cy), fill=c, width=stroke)
    elif "ceiling" in slug or "pop" in slug:
        draw.polygon([(x1 + w // 8, cy), (cx, y1 + h // 8), (x2 - w // 8, cy)], outline=c, width=stroke)
        draw.line((x1 + w // 8, cy, x2 - w // 8, cy), fill=c, width=stroke)
    elif "wardrobe" in slug or "almirah" in slug:
        draw.rectangle((x1 + w // 5, y1 + h // 8, x2 - w // 5, y2 - h // 8), outline=c, width=stroke)
        draw.line((cx, y1 + h // 8, cx, y2 - h // 8), fill=c, width=stroke)
    elif "kitchen" in slug or "bathroom" in slug:
        draw.rectangle((x1 + w // 6, y1 + h // 5, x2 - w // 6, y2 - h // 6), outline=c, width=stroke)
        draw.ellipse((cx - w // 12, cy, cx + w // 12, cy + h // 8), outline=c, width=stroke)
    elif "primer" in slug:
        draw.rounded_rectangle((cx - w // 10, y1 + h // 5, cx + w // 10, y2 - h // 5), radius=8, fill=c)
        draw.rectangle((cx - w // 20, y1 + h // 6, cx + w // 20, y1 + h // 4), fill=c)
    elif "putty" in slug or "crack" in slug:
        draw.rectangle((x1 + w // 6, y1 + h // 4, x2 - w // 6, y2 - h // 5), outline=c, width=stroke)
        draw.line((x1 + w // 4, cy, x2 - w // 4, cy - h // 10), fill=c, width=stroke)
        draw.line((x1 + w // 3, cy + h // 10, x2 - w // 3, cy), fill=c, width=stroke)
    elif "touch-up" in slug or "patch" in slug:
        draw.ellipse((cx - w // 8, cy - h // 8, cx + w // 8, cy + h // 8), outline=c, width=stroke)
        draw.line((cx + w // 10, cy - h // 10, cx + w // 4, cy - h // 4), fill=c, width=stroke)
    elif "scraping" in slug or "removal" in slug:
        draw.polygon([(x1 + w // 4, y2 - h // 5), (x2 - w // 5, y1 + h // 5), (x2 - w // 8, y1 + h // 4)], fill=c)
    elif "stain" in slug or "damp" in slug:
        draw.ellipse((cx - w // 10, cy - h // 12, cx + w // 10, cy + h // 12), fill=c)
        for dx in (-w // 8, 0, w // 8):
            draw.ellipse((cx + dx - w // 20, cy + h // 10, cx + dx + w // 20, cy + h // 5), fill=c)
    elif "waterproof" in slug or "weather" in slug:
        draw.polygon([(cx, y1 + h // 6), (x1 + w // 5, cy), (cx, y2 - h // 6), (x2 - w // 5, cy)], outline=c, width=stroke)
    elif "accent" in slug or "single-wall" in slug:
        draw.rectangle((x1 + w // 6, y1 + h // 5, x1 + w // 2, y2 - h // 6), fill=c)
        draw.rectangle((x1 + w // 2 + w // 16, y1 + h // 5, x2 - w // 6, y2 - h // 6), outline=c, width=stroke)
    elif "full-house-interior" in slug or "full-room" in slug:
        draw.polygon([(cx, y1 + h // 8), (x1 + w // 6, y1 + h // 3), (x2 - w // 6, y1 + h // 3)], outline=c, width=stroke)
        draw.rectangle((x1 + w // 5, y1 + h // 3, x2 - w // 5, y2 - h // 8), outline=c, width=stroke)
    elif "consultation" in slug:
        draw.rounded_rectangle((x1 + w // 5, y1 + h // 5, x2 - w // 5, y2 - h // 5), radius=10, outline=c, width=stroke)
        for y in range(y1 + h // 4, y2 - h // 4, h // 7):
            draw.line((x1 + w // 4, y, x2 - w // 4, y), fill=c, width=stroke // 2)
    else:
        draw.rounded_rectangle((x1 + w // 8, y1 + h // 5, x2 - w // 8, y2 - h // 6), radius=14, outline=c, width=stroke)
        draw.ellipse((cx - w // 12, y1 + h // 6, cx + w // 12, y1 + h // 3), fill=c)

    # paint roller accent
    draw.ellipse((x2 - w // 5, y2 - h // 4, x2 - w // 10, y2 - h // 8), fill=c)


def render_service_image(service: dict, size: tuple[int, int]) -> Image.Image:
    idx = sum(ord(c) for c in service["slug"]) % len(PALETTES)
    c1, c2, accent = PALETTES[idx]
    img = _gradient(size, c1, c2)
    draw = ImageDraw.Draw(img)
    w, h = size

    overlay = Image.new("RGBA", size, (255, 255, 255, 0))
    odraw = ImageDraw.Draw(overlay)
    odraw.ellipse((int(w * 0.55), int(-h * 0.15), int(w * 1.15), int(h * 0.55)), fill=(255, 255, 255, 35))
    img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")
    draw = ImageDraw.Draw(img)

    icon_box = (int(w * 0.08), int(h * 0.12), int(w * 0.38), int(h * 0.52))
    _draw_service_icon(draw, service["slug"], icon_box, accent)

    title_font = _font(int(h * 0.1), bold=True)
    sub_font = _font(int(h * 0.042))
    badge_font = _font(int(h * 0.032), bold=True)

    title = _wrap_title(service["name"])
    title_y = int(h * 0.56)
    for i, line in enumerate(title.split("\n")):
        draw.text((int(w * 0.08), title_y + i * int(h * 0.11)), line, fill="white", font=title_font)

    sub = _subtitle(service["slug"], service["sub_category_slug"])
    draw.text((int(w * 0.08), int(h * 0.82)), sub, fill=(245, 245, 245), font=sub_font)
    draw.rounded_rectangle((int(w * 0.08), int(h * 0.9), int(w * 0.42), int(h * 0.96)), radius=12, fill=(255, 255, 255, 40))
    draw.text((int(w * 0.11), int(h * 0.905)), "Panun Kaergar", fill="white", font=badge_font)
    return img


def subject_for_slug(slug: str, repair):
    if "door" in slug or "gate" in slug:
        return repair.draw_open_door
    if "window" in slug or "grille" in slug:
        return repair.draw_window
    if "wardrobe" in slug or "almirah" in slug:
        return repair.draw_wardrobe
    if "kitchen" in slug or "bathroom" in slug:
        return repair.draw_cabinet
    if "ceiling" in slug or "pop" in slug:
        return repair.draw_roof
    if "texture" in slug or "panel" in slug:
        return repair.draw_panels
    if any(k in slug for k in ("building", "full-house", "exterior-wall", "boundary", "full-room", "accent")):
        return repair.draw_furniture
    return repair.draw_open_door


def save_variant_icon(path: Path, drawer) -> None:
    big = SIZE * SCALE
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    drawer(ImageDraw.Draw(img))
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    path.parent.mkdir(parents=True, exist_ok=True)
    img.convert("RGB").save(path, "PNG", optimize=True)


def generate_service_images(catalog: dict) -> None:
    for service in catalog["services"]:
        out_dir = SERVICE_IMG / service["slug"]
        out_dir.mkdir(parents=True, exist_ok=True)
        render_service_image(service, (1536, 1024)).save(out_dir / "cover.png", "PNG", optimize=True)
        render_service_image(service, (1024, 1024)).save(out_dir / "thumbnail.png", "PNG", optimize=True)
        print(f"Service images: {service['slug']}")


def generate_variant_icons(catalog: dict) -> None:
    repair = load_repair_icons()
    for service in catalog["services"]:
        slug = service["slug"]
        subject = subject_for_slug(slug, repair)
        compose = repair.compose(subject)
        path = VARIANT_IMG / f"{slug}-book-site-inspection.png"
        save_variant_icon(path, compose)
        print(f"Variant icon: {path.name}")


def main() -> None:
    catalog = load_catalog()
    generate_service_images(catalog)
    generate_variant_icons(catalog)
    print(f"Done — {len(catalog['services'])} painting services.")


if __name__ == "__main__":
    main()
