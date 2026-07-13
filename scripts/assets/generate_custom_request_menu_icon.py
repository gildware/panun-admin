#!/usr/bin/env python3
"""Generate custom_request menu icons: clipboard + pencil (request form)."""

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


def draw_clipboard(draw: ImageDraw.ImageDraw, fill: int) -> None:
    board = (108, 118, 404, 418)
    draw.rounded_rectangle(board, radius=26, fill=fill)

    clip_w, clip_h = 92, 44
    clip_x = SIZE // 2 - clip_w // 2
    clip_y = 92
    draw.rounded_rectangle((clip_x, clip_y, clip_x + clip_w, clip_y + clip_h), radius=10, fill=fill)

    line_left = 148
    line_right = 364
    line_h = 18
    for y in (188, 248, 308):
        draw.rounded_rectangle((line_left, y, line_right, y + line_h), radius=9, fill=0)


def draw_pencil(draw: ImageDraw.ImageDraw, fill: int) -> None:
    body = [
        (286, 286),
        (392, 178),
        (418, 204),
        (312, 312),
    ]
    draw.polygon(body, fill=fill)

    tip = [
        (392, 178),
        (418, 204),
        (438, 184),
        (412, 158),
    ]
    draw.polygon(tip, fill=fill)

    draw.rounded_rectangle((270, 302, 322, 354), radius=8, fill=fill)
    draw.rounded_rectangle((276, 308, 316, 332), radius=4, fill=0)


def build_alpha() -> Image.Image:
    alpha = Image.new('L', (SIZE, SIZE), 0)
    draw = ImageDraw.Draw(alpha)
    draw_clipboard(draw, 255)
    draw_pencil(draw, 255)
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
