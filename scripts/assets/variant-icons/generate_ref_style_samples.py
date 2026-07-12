#!/usr/bin/env python3
"""Generate Book Site Inspection icons matching the reference style."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw

COLOR = "#1A233A"
SIZE = 512
SCALE = 2
OUT = Path(__file__).resolve().parent / "samples"


def s(v: int) -> int:
    return v * SCALE


def save_hires(name: str, drawer) -> None:
    big = SIZE * SCALE
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    drawer(ImageDraw.Draw(img))
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    OUT.mkdir(parents=True, exist_ok=True)
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def draw_checkmark(draw: ImageDraw.ImageDraw, x1: int, y1: int, x2: int, y2: int, x3: int, y3: int, width: int) -> None:
    draw.line((x1, y1, x2, y2), fill=COLOR, width=width)
    draw.line((x2, y2, x3, y3), fill=COLOR, width=width)


def draw_open_door(draw: ImageDraw.ImageDraw, ox: int, oy: int, frame_w: int, frame_h: int, swing: str = "right") -> None:
    """Door frame with ajar panel — reference style."""
    stroke = s(14)
    # Frame
    draw.rounded_rectangle((s(ox), s(oy), s(ox + frame_w), s(oy + frame_h)), radius=s(6), outline=COLOR, width=stroke)
    inset = s(12)
    fx1, fy1 = s(ox) + inset, s(oy) + inset
    fx2, fy2 = s(ox + frame_w) - inset, s(oy + frame_h) - inset
    draw.rectangle((fx1, fy1, fx2, fy2), outline=COLOR, width=s(8))

    # Ajar door panel
    gap = s(18)
    if swing == "right":
        px1, py1 = fx1 + gap, fy1 + gap
        px2, py2 = fx2 - gap * 2, fy2 - gap
        draw.polygon([(px1, py1), (px2, py1 - s(8)), (px2, py2), (px1, py2)], fill=COLOR)
        # Panels
        mid_x = (px1 + px2) // 2
        draw.line((mid_x, py1 + s(6), mid_x, py2 - s(6)), fill="white", width=s(5))
        draw.line((px1 + s(10), (py1 + py2) // 2, px2 - s(10), (py1 + py2) // 2), fill="white", width=s(4))
        # Knob
        draw.ellipse((px2 - s(28), (py1 + py2) // 2 - s(10), px2 - s(8), (py1 + py2) // 2 + s(10)), fill="white")
    else:
        px1, py1 = fx1 + gap * 2, fy1 + gap
        px2, py2 = fx2 - gap, fy2 - gap
        draw.polygon([(px1, py1 - s(8)), (px2, py1), (px2, py2), (px1, py2)], fill=COLOR)
        mid_x = (px1 + px2) // 2
        draw.line((mid_x, py1 + s(6), mid_x, py2 - s(6)), fill="white", width=s(5))
        draw.line((px1 + s(10), (py1 + py2) // 2, px2 - s(10), (py1 + py2) // 2), fill="white", width=s(4))
        draw.ellipse((px1 + s(8), (py1 + py2) // 2 - s(10), px1 + s(28), (py1 + py2) // 2 + s(10)), fill="white")


def draw_lens_check(draw: ImageDraw.ImageDraw, cx: int, cy: int, diameter: int) -> None:
    """Magnifying glass with checkmark inside."""
    half = s(diameter // 2)
    scx, scy = s(cx), s(cy)
    stroke = s(max(16, diameter // 10))
    draw.ellipse((scx - half, scy - half, scx + half, scy + half), outline=COLOR, width=stroke)
    # Handle
    hx = scx + int(half * 0.55)
    hy = scy + int(half * 0.55)
    draw.line((hx, hy, hx + s(int(diameter * 0.38)), hy + s(int(diameter * 0.38))), fill=COLOR, width=stroke + s(4))
    # Check inside lens
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


def draw_clipboard_pen(
    draw: ImageDraw.ImageDraw,
    x: int,
    y: int,
    w: int,
    h: int,
    pen_side: str = "right",
) -> None:
    """Clipboard with ticked boxes + pen."""
    sx, sy, sw, sh = s(x), s(y), s(w), s(h)
    draw.rounded_rectangle((sx, sy, sx + sw, sy + sh), radius=s(10), fill=COLOR)
    # Clip
    draw.rounded_rectangle((sx + s(16), sy - s(18), sx + sw - s(16), sy + s(4)), radius=s(5), fill=COLOR)
    draw.ellipse((sx + sw // 2 - s(10), sy - s(28), sx + sw // 2 + s(10), sy - s(8)), outline="white", width=s(4))

    row_y = sy + s(28)
    for _ in range(2):
        box = s(16)
        draw.rectangle((sx + s(14), row_y, sx + s(14) + box, row_y + box), outline="white", width=s(3))
        draw_checkmark(
            draw,
            sx + s(17),
            row_y + s(9),
            sx + s(21),
            row_y + s(13),
            sx + s(27),
            row_y + s(5),
            s(3),
        )
        draw.rounded_rectangle((sx + s(38), row_y + s(4), sx + sw - s(12), row_y + s(12)), radius=s(2), fill="white")
        row_y += s(30)

    # Third text line only
    draw.rounded_rectangle((sx + s(14), row_y + s(4), sx + sw - s(20), row_y + s(12)), radius=s(2), fill="white")

    # Pen
    if pen_side == "right":
        px1, py1 = sx + sw + s(8), sy + sh - s(40)
        px2, py2 = sx + sw + s(48), sy + s(20)
    else:
        px1, py1 = sx - s(48), sy + s(20)
        px2, py2 = sx - s(8), sy + sh - s(40)
    draw.line((px1, py1, px2, py2), fill=COLOR, width=s(10))
    draw.polygon([(px2, py2), (px2 + s(10), py2 + s(4)), (px2 - s(2), py2 + s(12))], fill=COLOR)


def compose_ref_v1(draw: ImageDraw.ImageDraw) -> None:
    """Classic — matches reference layout."""
    draw_open_door(draw, 58, 118, 118, 210, swing="right")
    draw_lens_check(draw, 198, 248, 132)
    draw_clipboard_pen(draw, 292, 132, 108, 148, pen_side="right")


def compose_ref_v2(draw: ImageDraw.ImageDraw) -> None:
    """Larger lens hero, door tucked left."""
    draw_open_door(draw, 42, 128, 108, 198, swing="right")
    draw_lens_check(draw, 218, 262, 148)
    draw_clipboard_pen(draw, 318, 148, 96, 132, pen_side="right")


def compose_ref_v3(draw: ImageDraw.ImageDraw) -> None:
    """Clipboard forward, lens behind door."""
    draw_open_door(draw, 48, 108, 122, 220, swing="left")
    draw_lens_check(draw, 178, 270, 120)
    draw_clipboard_pen(draw, 268, 108, 118, 158, pen_side="right")


def compose_ref_v4(draw: ImageDraw.ImageDraw) -> None:
    """Compact tight grouping."""
    draw_open_door(draw, 72, 138, 108, 188, swing="right")
    draw_lens_check(draw, 188, 258, 118)
    draw_clipboard_pen(draw, 278, 148, 100, 136, pen_side="right")


def compose_ref_v5(draw: ImageDraw.ImageDraw) -> None:
    """Taller door, lens lower, pen left of clipboard."""
    draw_open_door(draw, 52, 88, 128, 248, swing="right")
    draw_lens_check(draw, 210, 288, 128)
    draw_clipboard_pen(draw, 300, 118, 104, 152, pen_side="left")


SAMPLES = {
    "30-ref-classic": compose_ref_v1,
    "31-ref-lens-hero": compose_ref_v2,
    "32-ref-clipboard": compose_ref_v3,
    "33-ref-compact": compose_ref_v4,
    "34-ref-tall-door": compose_ref_v5,
}


def main() -> None:
    for name, drawer in SAMPLES.items():
        save_hires(name, drawer)


if __name__ == "__main__":
    main()
