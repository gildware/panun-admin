#!/usr/bin/env python3
"""Generate Book Site Inspection variant icons for carpentry repair services."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw

COLOR = "#1A233A"
SIZE = 512
SCALE = 2
OUT = Path(__file__).resolve().parent


def s(v: int) -> int:
    return v * SCALE


def save_icon(name: str, drawer) -> None:
    big = SIZE * SCALE
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    drawer(ImageDraw.Draw(img))
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def draw_checkmark(draw: ImageDraw.ImageDraw, x1: int, y1: int, x2: int, y2: int, x3: int, y3: int, width: int) -> None:
    draw.line((x1, y1, x2, y2), fill=COLOR, width=width)
    draw.line((x2, y2, x3, y3), fill=COLOR, width=width)


def draw_lens_check(draw: ImageDraw.ImageDraw, cx: int, cy: int, diameter: int) -> None:
    half = s(diameter // 2)
    scx, scy = s(cx), s(cy)
    stroke = s(max(16, diameter // 10))
    draw.ellipse((scx - half, scy - half, scx + half, scy + half), outline=COLOR, width=stroke)
    hx = scx + int(half * 0.55)
    hy = scy + int(half * 0.55)
    draw.line((hx, hy, hx + s(int(diameter * 0.38)), hy + s(int(diameter * 0.38))), fill=COLOR, width=stroke + s(4))
    w = s(max(10, diameter // 14))
    draw_checkmark(
        draw,
        scx - s(diameter // 5),
        scy + s(diameter // 14),
        scx - s(diameter // 14),
        scy + s(diameter // 4),
        scx + s(diameter // 4),
        scy - s(diameter // 5),
        w,
    )


def draw_clipboard_pen(draw: ImageDraw.ImageDraw, x: int, y: int, w: int, h: int) -> None:
    sx, sy, sw, sh = s(x), s(y), s(w), s(h)
    draw.rounded_rectangle((sx, sy, sx + sw, sy + sh), radius=s(10), fill=COLOR)
    draw.rounded_rectangle((sx + s(16), sy - s(18), sx + sw - s(16), sy + s(4)), radius=s(5), fill=COLOR)
    draw.ellipse((sx + sw // 2 - s(10), sy - s(28), sx + sw // 2 + s(10), sy - s(8)), outline="white", width=s(4))
    row_y = sy + s(28)
    for _ in range(2):
        box = s(16)
        draw.rectangle((sx + s(14), row_y, sx + s(14) + box, row_y + box), outline="white", width=s(3))
        draw_checkmark(draw, sx + s(17), row_y + s(9), sx + s(21), row_y + s(13), sx + s(27), row_y + s(5), s(3))
        draw.rounded_rectangle((sx + s(38), row_y + s(4), sx + sw - s(12), row_y + s(12)), radius=s(2), fill="white")
        row_y += s(30)
    draw.rounded_rectangle((sx + s(14), row_y + s(4), sx + sw - s(20), row_y + s(12)), radius=s(2), fill="white")
    px1, py1 = sx + sw + s(8), sy + sh - s(40)
    px2, py2 = sx + sw + s(48), sy + s(20)
    draw.line((px1, py1, px2, py2), fill=COLOR, width=s(10))
    draw.polygon([(px2, py2), (px2 + s(10), py2 + s(4)), (px2 - s(2), py2 + s(12))], fill=COLOR)


def draw_open_door(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    ox, oy, fw, fh = s(58), s(118), s(118), s(210)
    draw.rounded_rectangle((ox, oy, ox + fw, oy + fh), radius=s(6), outline=COLOR, width=stroke)
    inset = s(12)
    fx1, fy1 = ox + inset, oy + inset
    fx2, fy2 = ox + fw - inset, oy + fh - inset
    draw.rectangle((fx1, fy1, fx2, fy2), outline=COLOR, width=s(8))
    gap = s(18)
    px1, py1 = fx1 + gap, fy1 + gap
    px2, py2 = fx2 - gap * 2, fy2 - gap
    draw.polygon([(px1, py1), (px2, py1 - s(8)), (px2, py2), (px1, py2)], fill=COLOR)
    mid_x = (px1 + px2) // 2
    draw.line((mid_x, py1 + s(6), mid_x, py2 - s(6)), fill="white", width=s(5))
    draw.ellipse((px2 - s(28), (py1 + py2) // 2 - s(10), px2 - s(8), (py1 + py2) // 2 + s(10)), fill="white")


def draw_furniture(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    x1, y1, x2, y2 = s(70), s(170), s(200), s(300)
    draw.rounded_rectangle((x1, y1, x2, y2), radius=s(12), outline=COLOR, width=stroke)
    draw.line((s(135), y1, s(135), y2), fill=COLOR, width=stroke)
    draw.line((x1, s(235), x2, s(235)), fill=COLOR, width=s(8))


def draw_window(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    x1, y1, x2, y2 = s(62), s(120), s(200), s(290)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    cx, cy = (x1 + x2) // 2, (y1 + y2) // 2
    draw.line((cx, y1, cx, y2), fill=COLOR, width=stroke)
    draw.line((x1, cy, x2, cy), fill=COLOR, width=stroke)


def draw_wardrobe(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    x1, y1, x2, y2 = s(68), s(110), s(198), s(300)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    draw.line((s(133), y1, s(133), y2), fill=COLOR, width=stroke)
    draw.ellipse((s(88), s(205), s(118), s(235)), outline=COLOR, width=s(8))


def draw_cabinet(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    x1, y1, x2, y2 = s(64), s(130), s(204), s(300)
    draw.rectangle((x1, y1, x2, y2), outline=COLOR, width=stroke)
    draw.line((x1, s(215), x2, s(215)), fill=COLOR, width=stroke)
    draw.ellipse((s(125), s(245), s(145), s(265)), outline=COLOR, width=s(8))


def draw_panels(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(12)
    for i, px in enumerate([s(58), s(98), s(138), s(178)]):
        draw.rectangle((px, s(120), px + s(28), s(300)), outline=COLOR, width=stroke)
        if i == 1:
            draw.line((px + s(6), s(150), px + s(22), s(190)), fill=COLOR, width=s(6))


def draw_roof(draw: ImageDraw.ImageDraw) -> None:
    stroke = s(14)
    draw.polygon([(s(60), s(280)), (s(132), s(110)), (s(204), s(280))], outline=COLOR, width=stroke)
    draw.line((s(88), s(230), s(176), s(230)), fill=COLOR, width=s(8))


def compose(subject_drawer) -> None:
    def _draw(draw: ImageDraw.ImageDraw) -> None:
        subject_drawer(draw)
        draw_lens_check(draw, 198, 248, 132)
        draw_clipboard_pen(draw, 292, 132, 108, 148)

    return _draw


ICONS = {
    "door-repair-book-site-inspection": compose(draw_open_door),
    "furniture-repair-book-site-inspection": compose(draw_furniture),
    "window-repair-book-site-inspection": compose(draw_window),
    "wardrobe-repair-book-site-inspection": compose(draw_wardrobe),
    "kitchen-cabinet-repair-book-site-inspection": compose(draw_cabinet),
    "wooden-panel-repair-book-site-inspection": compose(draw_panels),
    "roof-repair-book-site-inspection": compose(draw_roof),
    "book-site-inspection": compose(draw_open_door),
}


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    for name, drawer in ICONS.items():
        save_icon(name, drawer)


if __name__ == "__main__":
    main()
