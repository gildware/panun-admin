#!/usr/bin/env python3
"""Fix variation icons from live audit: recolor washed, regenerate wrong-size/missing."""

from __future__ import annotations

import json
import math
from pathlib import Path

from PIL import Image, ImageDraw

BRAND = (0x1A, 0x23, 0x3A, 255)
WHITE = (255, 255, 255, 255)
SIZE = 512
ROOT = Path(__file__).resolve().parent
OUT = ROOT / "variant-icons"
AUDIT = Path("/tmp/pk-variant-audit-classified.json")
RAW = Path("/tmp/pk-variant-audit-results.json")


def canvas() -> tuple[Image.Image, ImageDraw.ImageDraw]:
    img = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    return img, ImageDraw.Draw(img)


def punch_circle(img: Image.Image, cx: int, cy: int, r: int) -> None:
    px = img.load()
    r2 = r * r
    for y in range(max(0, cy - r - 1), min(SIZE, cy + r + 2)):
        for x in range(max(0, cx - r - 1), min(SIZE, cx + r + 2)):
            if (x - cx) ** 2 + (y - cy) ** 2 <= r2:
                px[x, y] = (0, 0, 0, 0)


def quantize(img: Image.Image) -> Image.Image:
    px = img.load()
    for y in range(SIZE):
        for x in range(SIZE):
            a = px[x, y][3]
            if a < 40:
                px[x, y] = (0, 0, 0, 0)
            else:
                px[x, y] = BRAND
    return img


def save_rgb(slug: str, key: str, img: Image.Image) -> Path:
    img = quantize(img)
    rgb = Image.new("RGBA", (SIZE, SIZE), WHITE)
    rgb.alpha_composite(img)
    OUT.mkdir(parents=True, exist_ok=True)
    path = OUT / f"{slug}-{key}.png"
    rgb.convert("RGB").save(path, "PNG", optimize=True)
    return path


def recolor_existing(src: Path) -> Image.Image:
    im = Image.open(src).convert("RGBA")
    # Fit content into square 512 with padding
    px = im.load()
    w, h = im.size
    mask = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    mp = mask.load()
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            if a < 20:
                continue
            if r > 235 and g > 235 and b > 235:
                continue
            if (r + g + b) / 3 > 230:
                continue
            mp[x, y] = BRAND
    xs, ys = [], []
    for y in range(h):
        for x in range(w):
            if mp[x, y][3] > 0:
                xs.append(x)
                ys.append(y)
    if not xs:
        # fallback: threshold dark pixels
        for y in range(h):
            for x in range(w):
                r, g, b, a = px[x, y]
                if a > 20 and (r + g + b) / 3 < 200:
                    mp[x, y] = BRAND
                    xs.append(x)
                    ys.append(y)
    if not xs:
        raise RuntimeError(f"no ink in {src}")
    cropped = mask.crop((min(xs), min(ys), max(xs) + 1, max(ys) + 1))
    cw, ch = cropped.size
    pad = 0.14
    side = max(int(max(cw, ch) / (1 - 2 * pad)), max(cw, ch) + 2)
    canvas_img = Image.new("RGBA", (side, side), (0, 0, 0, 0))
    canvas_img.paste(cropped, ((side - cw) // 2, (side - ch) // 2), cropped)
    return canvas_img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)


def draw_clipboard(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((160, 120, 352, 420), radius=18, fill=BRAND)
    draw.rounded_rectangle((210, 90, 302, 140), radius=10, fill=BRAND)
    for y in (190, 240, 290, 340):
        draw.rounded_rectangle((190, y, 322, y + 18), radius=6, fill=BRAND)


def draw_magnifier(img: Image.Image, draw: ImageDraw.ImageDraw, cx=320, cy=320) -> None:
    draw.ellipse((cx - 70, cy - 70, cx + 70, cy + 70), outline=BRAND, width=18)
    draw.line([(cx + 50, cy + 50), (cx + 110, cy + 110)], fill=BRAND, width=18)


def draw_car(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((110, 230, 400, 320), radius=20, fill=BRAND)
    draw.polygon([(150, 230), (200, 170), (320, 170), (370, 230)], fill=BRAND)
    draw.ellipse((150, 290, 220, 360), fill=BRAND)
    draw.ellipse((300, 290, 370, 360), fill=BRAND)


def draw_bike(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((100, 260, 200, 360), outline=BRAND, width=16)
    draw.ellipse((310, 260, 410, 360), outline=BRAND, width=16)
    draw.line([(150, 310), (256, 200), (360, 310)], fill=BRAND, width=14)
    draw.line([(256, 200), (256, 160)], fill=BRAND, width=14)
    draw.rounded_rectangle((230, 140, 310, 170), radius=8, fill=BRAND)


def draw_leaf(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((170, 120, 360, 380), fill=BRAND)
    draw.line([(256, 140), (256, 400)], fill=BRAND, width=10)
    draw.line([(256, 240), (180, 180)], fill=BRAND, width=8)
    draw.line([(256, 280), (340, 200)], fill=BRAND, width=8)


def draw_mower(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((140, 220, 370, 320), radius=16, fill=BRAND)
    draw.ellipse((160, 300, 240, 380), fill=BRAND)
    draw.ellipse((280, 300, 360, 380), fill=BRAND)
    draw.line([(256, 220), (256, 140)], fill=BRAND, width=16)
    draw.rounded_rectangle((230, 110, 300, 150), radius=10, fill=BRAND)


def draw_hand(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((180, 140, 330, 300), fill=BRAND)
    draw.rounded_rectangle((210, 280, 300, 400), radius=20, fill=BRAND)
    for x in (195, 230, 265, 300):
        draw.rounded_rectangle((x, 110, x + 22, 180), radius=8, fill=BRAND)


def draw_foot(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((150, 180, 360, 340), fill=BRAND)
    draw.rounded_rectangle((200, 300, 310, 420), radius=24, fill=BRAND)


def draw_scissors(draw: ImageDraw.ImageDraw, img: Image.Image) -> None:
    draw.ellipse((140, 300, 200, 360), outline=BRAND, width=14)
    draw.ellipse((220, 300, 280, 360), outline=BRAND, width=14)
    draw.line([(170, 300), (300, 140)], fill=BRAND, width=14)
    draw.line([(250, 300), (170, 140)], fill=BRAND, width=14)
    draw.ellipse((200, 240, 240, 280), fill=BRAND)


def draw_comb(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((320, 120, 360, 400), radius=8, fill=BRAND)
    for y in range(140, 380, 22):
        draw.rectangle((360, y, 410, y + 10), fill=BRAND)


def draw_face(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((150, 120, 360, 380), fill=BRAND)


def draw_thread(draw: ImageDraw.ImageDraw) -> None:
    draw.ellipse((160, 140, 350, 360), outline=BRAND, width=16)
    draw.arc((180, 180, 330, 320), 200, 340, fill=BRAND, width=12)


def draw_wax(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((180, 140, 330, 360), radius=20, fill=BRAND)
    draw.polygon([(200, 140), (310, 140), (256, 80)], fill=BRAND)


def draw_bottle(draw: ImageDraw.ImageDraw) -> None:
    draw.rounded_rectangle((210, 160, 300, 400), radius=18, fill=BRAND)
    draw.rounded_rectangle((230, 110, 280, 170), radius=8, fill=BRAND)


def draw_sparkle(draw: ImageDraw.ImageDraw, cx: int, cy: int, s: int = 24) -> None:
    draw.polygon([(cx, cy - s), (cx + s * 0.35, cy), (cx, cy + s), (cx - s * 0.35, cy)], fill=BRAND)
    draw.polygon([(cx - s, cy), (cx, cy - s * 0.35), (cx + s, cy), (cx, cy + s * 0.35)], fill=BRAND)


def generate_for(service_slug: str, variant_key: str, service: str, variant: str) -> Image.Image:
    img, d = canvas()
    text = f"{service_slug} {variant_key} {service} {variant}".lower()

    if any(k in text for k in ("inspect", "inspection")):
        draw_clipboard(d)
        draw_magnifier(img, d)
    elif any(k in text for k in ("car", "sedan", "suv", "hatchback", "vehicle")) and "bike" not in text and "scooter" not in text:
        draw_car(d)
        if "wash" in text or "clean" in text or "detail" in text:
            draw_sparkle(d, 380, 160, 22)
        if "battery" in text:
            d.rounded_rectangle((360, 140, 430, 220), radius=8, fill=BRAND)
        if "tyre" in text or "tire" in text or "puncture" in text:
            d.ellipse((360, 280, 450, 370), outline=BRAND, width=14)
    elif any(k in text for k in ("bike", "scooter", "two-wheeler", "100-150", "150cc")):
        draw_bike(d)
    elif any(k in text for k in ("lawn", "mow", "garden", "grass", "hedge", "prun", "plant", "leaf", "soil", "irrig", "terrace", "weeding", "seasonal", "monthly")):
        if "mow" in text or "lawn" in text:
            draw_mower(d)
        else:
            draw_leaf(d)
    elif any(k in text for k in ("manicure", "mani", "nail", "cut-file", "polish")):
        draw_hand(d)
        draw_sparkle(d, 380, 160, 20)
    elif any(k in text for k in ("pedicure", "pedi", "feet")):
        draw_foot(d)
        draw_sparkle(d, 380, 160, 20)
    elif any(k in text for k in ("hair-cut", "cutting", "v-cut", "u-cut", "layered", "flicks", "straightening", "styling")):
        draw_scissors(d, img)
        draw_comb(d)
    elif any(k in text for k in ("color", "henna", "highlight", "streak", "keratin", "root")):
        draw_bottle(d)
        draw_sparkle(d, 360, 140, 22)
    elif any(k in text for k in ("facial", "cleanup", "face cleanup", "anti", "pearl", "lotus", "herbal", "diamond", "daimond", "vlcc", "kanepeki", "o3")):
        draw_face(d)
        draw_sparkle(d, 380, 150, 22)
    elif "thread" in text:
        draw_thread(d)
    elif "wax" in text:
        draw_wax(d)
    elif any(k in text for k in ("massage", "polish")):
        draw_face(d)
        d.ellipse((300, 280, 420, 380), fill=BRAND)
    else:
        # generic service mark
        d.rounded_rectangle((150, 150, 362, 362), radius=28, fill=BRAND)
        draw_sparkle(d, 256, 256, 40)

    return img


def resolve_local(slug: str, key: str) -> Path | None:
    p1 = OUT / f"{slug}-{key}.png"
    p2 = OUT / f"{key}.png"
    if p1.is_file():
        return p1
    if p2.is_file():
        return p2
    return None


def main() -> None:
    classified = json.loads(AUDIT.read_text())["rows"]
    fixed = 0
    regenerated = 0
    failed: list[str] = []

    for row in classified:
        if row["status"] == "pass":
            continue
        slug = row["service_slug"]
        key = row["variant_key"]
        status = row["status"]
        out_name = f"{slug}-{key}.png"

        try:
            if status in ("bad_color", "stretched"):
                src = resolve_local(slug, key)
                if src is None:
                    # regenerate fallback
                    img = generate_for(slug, key, row["service"], row["variant"])
                    save_rgb(slug, key, img)
                    regenerated += 1
                else:
                    fitted = recolor_existing(src)
                    save_rgb(slug, key, fitted)
                    fixed += 1
            elif status in ("wrong_size", "missing", "cdn_broken"):
                img = generate_for(slug, key, row["service"], row["variant"])
                save_rgb(slug, key, img)
                regenerated += 1
            else:
                src = resolve_local(slug, key)
                if src:
                    fitted = recolor_existing(src)
                    save_rgb(slug, key, fitted)
                    fixed += 1
                else:
                    img = generate_for(slug, key, row["service"], row["variant"])
                    save_rgb(slug, key, img)
                    regenerated += 1
        except Exception as e:
            failed.append(f"{out_name}: {e}")

    manifest = [
        {
            "service_slug": r["service_slug"],
            "variant_key": r["variant_key"],
            "status": r["status"],
            "asset": f"{r['service_slug']}-{r['variant_key']}.png",
        }
        for r in classified
        if r["status"] != "pass"
    ]
    Path("/tmp/pk-variant-fix-manifest.json").write_text(json.dumps(manifest, indent=2))
    print(f"Recolored: {fixed}")
    print(f"Regenerated: {regenerated}")
    print(f"Failed: {len(failed)}")
    for f in failed[:20]:
        print(" ", f)
    print(f"Manifest: {len(manifest)} items -> /tmp/pk-variant-fix-manifest.json")


if __name__ == "__main__":
    main()
