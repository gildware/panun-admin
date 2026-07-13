#!/usr/bin/env python3
"""Strip baked-in backgrounds from generated menu icons and rebuild dark variants."""

from __future__ import annotations

from pathlib import Path

import numpy as np
from PIL import Image

ROOT = Path(__file__).resolve().parents[2].parent / 'design-previews' / 'menu-icons'
LIGHT_COLOR = np.array([37, 39, 77], dtype=np.uint8)  # #25274D
DARK_COLOR = np.array([255, 255, 255], dtype=np.uint8)


def build_alpha(rgb: np.ndarray) -> np.ndarray:
    r, g, b = rgb[:, :, 0], rgb[:, :, 1], rgb[:, :, 2]
    maxc = np.maximum(np.maximum(r, g), b).astype(np.int16)
    minc = np.minimum(np.minimum(r, g), b).astype(np.int16)
    mean = (r.astype(np.int16) + g.astype(np.int16) + b.astype(np.int16)) / 3

    bg = (maxc - minc <= 18) & (mean >= 205)
    fg = (~bg) & ((mean < 200) | (maxc - minc > 18))

    alpha = np.zeros(rgb.shape[:2], dtype=np.uint8)
    alpha[fg] = 255

    edge = (~bg) & (~fg)
    ys, xs = np.where(edge)
    for y, x in zip(ys, xs):
        if maxc[y, x] - minc[y, x] <= 18 and mean[y, x] >= 180:
            alpha[y, x] = 0
        else:
            alpha[y, x] = int(255 - min(255, (mean[y, x] - 180) * 4))

    return alpha


def render_icon(color: np.ndarray, alpha: np.ndarray) -> Image.Image:
    rgba = np.zeros((*alpha.shape, 4), dtype=np.uint8)
    rgba[:, :, :3] = color
    rgba[:, :, 3] = alpha
    return Image.fromarray(rgba, 'RGBA')


def load_alpha(path: Path) -> np.ndarray:
    rgba = np.array(Image.open(path).convert('RGBA'))
    alpha = rgba[:, :, 3]
    if np.any(alpha > 0):
        return alpha
    return build_alpha(np.array(Image.open(path).convert('RGB')))


def trim_and_fit(alpha: np.ndarray, margin_ratio: float = 0.08, output_size: int = 512) -> np.ndarray:
    ys, xs = np.where(alpha > 16)
    if len(xs) == 0:
        return alpha

    x0, x1 = int(xs.min()), int(xs.max())
    y0, y1 = int(ys.min()), int(ys.max())
    cropped = alpha[y0 : y1 + 1, x0 : x1 + 1]
    height, width = cropped.shape
    side = max(height, width)
    margin = max(1, int(side * margin_ratio))
    canvas_side = side + margin * 2
    canvas = np.zeros((canvas_side, canvas_side), dtype=np.uint8)
    offset_y = (canvas_side - height) // 2
    offset_x = (canvas_side - width) // 2
    canvas[offset_y : offset_y + height, offset_x : offset_x + width] = cropped
    return np.array(
        Image.fromarray(canvas, 'L').resize((output_size, output_size), Image.Resampling.LANCZOS)
    )


def process_pair(light_path: Path, dark_path: Path) -> None:
    alpha = trim_and_fit(load_alpha(light_path))
    render_icon(LIGHT_COLOR, alpha).save(light_path, 'PNG')
    render_icon(DARK_COLOR, alpha).save(dark_path, 'PNG')


def main() -> None:
    for app in ('customer', 'provider'):
        app_dir = ROOT / app
        keys = sorted({p.name.replace('_icon_light.png', '') for p in app_dir.glob('*_icon_light.png')})
        for key in keys:
            process_pair(app_dir / f'{key}_icon_light.png', app_dir / f'{key}_icon_dark.png')
            print(f'processed {app}/{key}')


if __name__ == '__main__':
    main()
