#!/usr/bin/env python3
"""Copy and resize home appliance service images; recolor variant icons to #1A233A."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

BRAND = (26, 35, 58)
SRC = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
ROOT = Path(__file__).resolve().parent
SERVICE_IMG = ROOT / "service-images"
VARIANT_IMG = ROOT / "variant-icons"

SERVICE_SLUGS = [
    "ac-installation",
    "ac-repair",
    "ac-servicing",
    "ac-uninstallation",
    "inverter-installation",
    "inverter-repair",
    "cctv-repair",
    "geyser-cleaning",
    "geyser-installation",
    "geyser-repair",
    "tv-installation",
    "tv-repair",
    "refrigerator-installation",
    "refrigerator-repair",
    "fan",
    "induction-heaters",
    "oven",
    "vacum-cleaner",
    "washing-machine-installation",
    "washing-machine-repair",
    "ro-service",
]


def recolor(img: Image.Image) -> Image.Image:
    img = img.convert("RGBA")
    px = img.load()
    w, h = img.size
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            if a < 20:
                px[x, y] = (255, 255, 255, 0)
                continue
            if r > 235 and g > 235 and b > 235:
                px[x, y] = (255, 255, 255, 255)
                continue
            strength = max(0, min(255, 255 - (r + g + b) // 3))
            if strength < 12:
                px[x, y] = (255, 255, 255, 255)
                continue
            blend = strength / 255
            nr = int(255 + (BRAND[0] - 255) * blend)
            ng = int(255 + (BRAND[1] - 255) * blend)
            nb = int(255 + (BRAND[2] - 255) * blend)
            px[x, y] = (nr, ng, nb, 255)
    return img


def save_service_image(slug: str, kind: str) -> None:
    src = SRC / f"{slug}-{kind}.png"
    if not src.is_file():
        raise SystemExit(f"Missing source image: {src}")
    out_dir = SERVICE_IMG / slug
    out_dir.mkdir(parents=True, exist_ok=True)
    img = Image.open(src).convert("RGB")
    if kind == "thumbnail":
        img = img.resize((1024, 1024), Image.Resampling.LANCZOS)
    else:
        img = img.resize((1536, 1024), Image.Resampling.LANCZOS)
    out = out_dir / f"{kind}.png"
    img.save(out, "PNG", optimize=True)
    print(f"Wrote {out}")


def save_variant_icon(src_name: str, out_name: str) -> None:
    src = SRC / src_name
    if not src.is_file():
        raise SystemExit(f"Missing variant source: {src}")
    out = VARIANT_IMG / out_name
    result = recolor(Image.open(src))
    result = result.resize((512, 512), Image.Resampling.LANCZOS)
    result.convert("RGB").save(out, "PNG", optimize=True)
    print(f"Wrote {out}")


def main() -> None:
    for slug in SERVICE_SLUGS:
        save_service_image(slug, "thumbnail")
        save_service_image(slug, "cover")

    variant_map = {
        "split-ac-upto-1-5-ton-src.png": "split-ac-upto-1-5-ton.png",
        "split-ac-1-5-to-2-ton-src.png": "split-ac-1-5-to-2-ton.png",
        "window-ac-install-src.png": "window-ac-install.png",
        "extra-copper-piping-src.png": "extra-copper-piping.png",
        "ac-repair-book-site-inspection-src.png": "ac-repair-book-site-inspection.png",
        "ac-general-servicing-src.png": "ac-general-servicing.png",
        "ac-uninstallation-src.png": "ac-uninstallation.png",
        "inverter-repair-book-site-inspection-src.png": "inverter-repair-book-site-inspection.png",
        "inverter-installation-src.png": "inverter-installation.png",
        "cctv-repair-book-site-inspection-src.png": "cctv-repair-book-site-inspection.png",
        "geyser-cleaning-src.png": "geyser-cleaning.png",
        "geyser-installation-src.png": "geyser-installation.png",
        "geyser-repair-book-site-inspection-src.png": "geyser-repair-book-site-inspection.png",
        "tv-installation-src.png": "tv-installation.png",
        "tv-repair-book-site-inspection-src.png": "tv-repair-book-site-inspection.png",
        "refrigerator-installation-src.png": "refrigerator-installation.png",
        "refrigerator-repair-book-site-inspection-src.png": "refrigerator-repair-book-site-inspection.png",
        "fan-book-site-inspection-src.png": "fan-book-site-inspection.png",
        "induction-heaters-book-site-inspection-src.png": "induction-heaters-book-site-inspection.png",
        "oven-book-site-inspection-src.png": "oven-book-site-inspection.png",
        "vacum-cleaner-book-site-inspection-src.png": "vacum-cleaner-book-site-inspection.png",
        "washing-machine-installation-src.png": "washing-machine-installation.png",
        "washing-machine-repair-book-site-inspection-src.png": "washing-machine-repair-book-site-inspection.png",
        "ro-service-src.png": "ro-service.png",
        "book-site-inspection-src.png": "book-site-inspection.png",
    }

    for src_name, out_name in variant_map.items():
        if (SRC / src_name).is_file():
            save_variant_icon(src_name, out_name)

    print("Done preparing appliance assets.")


if __name__ == "__main__":
    main()
