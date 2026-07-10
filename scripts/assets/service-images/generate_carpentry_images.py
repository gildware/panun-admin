#!/usr/bin/env python3
"""Generate thumbnail + cover PNGs for carpentry installation services."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent

SERVICES = [
    {
        "slug": "furniture-installation",
        "title": "Furniture\nInstallation",
        "subtitle": "Expert assembly at home",
        "colors": ("#8B5E3C", "#D4A574"),
        "accent": "#F5E6D3",
    },
    {
        "slug": "kitchen-cabinet-installation",
        "title": "Kitchen Cabinet\nInstallation",
        "subtitle": "Organized kitchen fitting",
        "colors": ("#2F6B4F", "#6BBF8A"),
        "accent": "#E8F5EE",
    },
    {
        "slug": "wardrobe-installation",
        "title": "Wardrobe\nInstallation",
        "subtitle": "Secure bedroom storage",
        "colors": ("#2C4A7C", "#6B9BD1"),
        "accent": "#E8F0FA",
    },
    {
        "slug": "wooden-panel-installation",
        "title": "Wooden Panel\nInstallation",
        "subtitle": "Neat wall panel fitting",
        "colors": ("#6B4F3A", "#B08968"),
        "accent": "#F3E8DC",
    },
    {
        "slug": "roof-installation",
        "title": "Roof\nInstallation",
        "subtitle": "Strong wooden roof fitting",
        "colors": ("#3D4F5F", "#7A95A8"),
        "accent": "#E8EEF2",
    },
]


def _font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/Library/Fonts/Arial Bold.ttf" if bold else "/Library/Fonts/Arial.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
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


def _draw_icon(draw: ImageDraw.ImageDraw, slug: str, box: tuple[int, int, int, int], accent: str) -> None:
    x1, y1, x2, y2 = box
    w, h = x2 - x1, y2 - y1
    cx, cy = (x1 + x2) // 2, (y1 + y2) // 2
    stroke = max(4, w // 40)
    color = accent

    if slug == "furniture-installation":
        draw.rounded_rectangle((x1, y1 + h // 4, x2, y2), radius=18, outline=color, width=stroke)
        draw.line((cx, y1 + h // 4, cx, y2), fill=color, width=stroke)
    elif slug == "kitchen-cabinet-installation":
        draw.rectangle((x1, y1 + h // 5, x2, y2), outline=color, width=stroke)
        draw.line((x1, cy, x2, cy), fill=color, width=stroke)
        draw.ellipse((cx - w // 10, cy + h // 10, cx + w // 10, cy + h // 5), outline=color, width=stroke)
    elif slug == "wardrobe-installation":
        draw.rectangle((x1 + w // 8, y1, x2 - w // 8, y2), outline=color, width=stroke)
        draw.line((cx, y1, cx, y2), fill=color, width=stroke)
        draw.ellipse((x1 + w // 5, cy - h // 10, x1 + w // 3, cy + h // 10), outline=color, width=stroke)
    elif slug == "wooden-panel-installation":
        for i in range(4):
            px = x1 + i * (w // 4) + 8
            draw.rectangle((px, y1, px + w // 6, y2), outline=color, width=stroke)
    else:
        draw.polygon([(x1, y2), (cx, y1), (x2, y2)], outline=color, width=stroke)
        draw.line((x1 + w // 6, y2 - h // 6, x2 - w // 6, y2 - h // 6), fill=color, width=stroke)


def render_image(service: dict, size: tuple[int, int], variant: str) -> Image.Image:
    img = _gradient(size, service["colors"][0], service["colors"][1])
    draw = ImageDraw.Draw(img)
    w, h = size

    overlay = Image.new("RGBA", size, (255, 255, 255, 0))
    odraw = ImageDraw.Draw(overlay)
    odraw.ellipse((w * 0.55, -h * 0.15, w * 1.15, h * 0.55), fill=(255, 255, 255, 35))
    img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")
    draw = ImageDraw.Draw(img)

    icon_box = (int(w * 0.08), int(h * 0.12), int(w * 0.38), int(h * 0.52))
    _draw_icon(draw, service["slug"], icon_box, service["accent"])

    title_font = _font(int(h * 0.11), bold=True)
    sub_font = _font(int(h * 0.045))
    badge_font = _font(int(h * 0.035), bold=True)

    title_y = int(h * 0.56) if variant == "cover" else int(h * 0.58)
    for i, line in enumerate(service["title"].split("\n")):
        draw.text((int(w * 0.08), title_y + i * int(h * 0.12)), line, fill="white", font=title_font)

    draw.text((int(w * 0.08), int(h * 0.82)), service["subtitle"], fill=(245, 245, 245), font=sub_font)
    draw.rounded_rectangle((int(w * 0.08), int(h * 0.9), int(w * 0.42), int(h * 0.96)), radius=12, fill=(255, 255, 255, 40))
    draw.text((int(w * 0.11), int(h * 0.905)), "Panun Kaergar", fill="white", font=badge_font)

    return img


def main() -> None:
    for service in SERVICES:
        out_dir = ROOT / service["slug"]
        out_dir.mkdir(parents=True, exist_ok=True)
        render_image(service, (1200, 800), "cover").save(out_dir / "cover.png", "PNG", optimize=True)
        render_image(service, (800, 800), "thumb").save(out_dir / "thumbnail.png", "PNG", optimize=True)
        print(f"Generated images for {service['slug']}")


if __name__ == "__main__":
    main()
