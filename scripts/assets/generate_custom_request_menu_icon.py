#!/usr/bin/env python3
"""Generate custom_request menu icons (light + dark) matching other menu icon style."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw

SIZE = 512
LIGHT_COLOR = (37, 39, 77, 255)  # #25274D
DARK_COLOR = (255, 255, 255, 255)

OUT_DIRS = [
    Path(__file__).resolve().parents[2].parent / 'design-previews' / 'menu-icons' / 'customer',
    Path(__file__).resolve().parents[2].parent / 'User app' / 'assets' / 'images',
]


def draw_document_body(draw: ImageDraw.ImageDraw, fill: int) -> None:
    left, top, right, bottom = 88, 96, 424, 416
    fold = 56
    radius = 28

    draw.rounded_rectangle((left, top, right, bottom), radius=radius, fill=fill)

    fold_x = right - fold
    draw.polygon([(fold_x, top), (right, top + fold), (fold_x, top + fold)], fill=fill)

    inner_left = left + 18
    inner_top = top + 18
    inner_right = right - fold - 10
    inner_bottom = bottom - 18
    draw.rounded_rectangle(
        (inner_left, inner_top, inner_right, inner_bottom),
        radius=18,
        fill=fill,
    )


def punch_question_mark(draw: ImageDraw.ImageDraw) -> None:
    cx = 248
    cy = 262
    stroke = 34

    draw.arc((cx - 58, cy - 92, cx + 58, cy + 18), start=200, end=340, fill=0, width=stroke)
    draw.rounded_rectangle(
        (cx - stroke // 2, cy + 8, cx + stroke // 2, cy + 58),
        radius=stroke // 2,
        fill=0,
    )
    dot_r = 16
    draw.ellipse((cx - dot_r, cy + 74, cx + dot_r, cy + 74 + dot_r * 2), fill=0)


def build_alpha() -> Image.Image:
    alpha = Image.new('L', (SIZE, SIZE), 0)
    draw = ImageDraw.Draw(alpha)
    draw_document_body(draw, 255)
    punch_question_mark(draw)
    return alpha


def render_icon(color: tuple[int, int, int, int], alpha: Image.Image) -> Image.Image:
    rgba = Image.new('RGBA', (SIZE, SIZE), (0, 0, 0, 0))
    color_layer = Image.new('RGBA', (SIZE, SIZE), color)
    rgba.paste(color_layer, mask=alpha)
    return rgba


def main() -> None:
    alpha = build_alpha()
    light = render_icon(LIGHT_COLOR, alpha)
    dark = render_icon(DARK_COLOR, alpha)

    for out_dir in OUT_DIRS:
        out_dir.mkdir(parents=True, exist_ok=True)
        light_path = out_dir / 'custom_request_icon_light.png'
        dark_path = out_dir / 'custom_request_icon_dark.png'
        light.save(light_path, 'PNG')
        dark.save(dark_path, 'PNG')
        print(f'Wrote {light_path}')
        print(f'Wrote {dark_path}')


if __name__ == '__main__':
    main()
