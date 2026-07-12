#!/usr/bin/env python3
"""Generate sample icons for Book Site Inspection (door installation)."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw

COLOR = "#1A233A"
SIZE = 512
OUT = Path(__file__).resolve().parent / "samples"


def canvas() -> tuple[Image.Image, ImageDraw.ImageDraw]:
    img = Image.new("RGBA", (SIZE, SIZE), (255, 255, 255, 255))
    return img, ImageDraw.Draw(img)


def save(name: str, img: Image.Image) -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def draw_door_frame(draw: ImageDraw.ImageDraw, x: int, y: int, w: int, h: int) -> None:
    """Simple door in frame."""
    draw.rounded_rectangle((x, y, x + w, y + h), radius=10, outline=COLOR, width=16)
    draw.rectangle((x + 18, y + 18, x + w - 18, y + h - 18), fill=COLOR)
    draw.ellipse((x + w - 70, y + h // 2 - 15, x + w - 40, y + h // 2 + 15), fill="white")
    draw.line((x + w // 2, y + 18, x + w // 2, y + h - 18), fill="white", width=5)


def draw_hard_hat(draw: ImageDraw.ImageDraw, cx: int, top: int, w: int = 72) -> None:
    half = w // 2
    draw.polygon([(cx - half, top + 28), (cx + half, top + 28), (cx + half - 8, top), (cx - half + 8, top)], fill=COLOR)
    draw.rectangle((cx - half + 10, top + 28, cx + half - 10, top + 38), fill=COLOR)


def draw_pro_head(draw: ImageDraw.ImageDraw, cx: int, cy: int, r: int = 28, with_hat: bool = True) -> None:
    if with_hat:
        draw_hard_hat(draw, cx, cy - r - 8, w=r * 2 + 10)
    draw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=COLOR)


def draw_clipboard(
    draw: ImageDraw.ImageDraw,
    x: int,
    y: int,
    w: int = 70,
    h: int = 100,
    checks: int = 2,
) -> None:
    draw.rounded_rectangle((x, y, x + w, y + h), radius=8, fill=COLOR)
    draw.rounded_rectangle((x + 10, y - 14, x + w - 10, y + 6), radius=4, fill=COLOR)
    draw.ellipse((x + w // 2 - 8, y - 22, x + w // 2 + 8, y - 6), outline="white", width=3)
    line_y = y + 18
    for i in range(4):
        lw = w - 24 - (i % 2) * 8
        draw.rectangle((x + 12, line_y, x + 12 + lw, line_y + 6), fill="white")
        if i < checks:
            draw.line((x + w - 14, line_y + 1, x + w - 6, line_y + 9), fill="white", width=3)
            draw.line((x + w - 6, line_y + 1, x + w - 18, line_y + 13), fill="white", width=3)
        line_y += 18


def draw_lens(draw: ImageDraw.ImageDraw, cx: int, cy: int, size: int = 44, angle: str = "down-right") -> None:
    """Magnifying glass / inspection lens."""
    half = size // 2
    draw.ellipse((cx - half, cy - half, cx + half, cy + half), outline=COLOR, width=max(10, size // 8))
    handle_len = max(28, size // 2)
    if angle == "down-right":
        draw.line((cx + half - 6, cy + half - 6, cx + half + handle_len, cy + half + handle_len), fill=COLOR, width=max(12, size // 6))
    elif angle == "down-left":
        draw.line((cx - half + 6, cy + half - 6, cx - half - handle_len, cy + half + handle_len), fill=COLOR, width=max(12, size // 6))
    else:
        draw.line((cx, cy + half, cx, cy + half + handle_len), fill=COLOR, width=max(12, size // 6))
    draw.arc((cx - half + 8, cy - half + 8, cx - 4, cy + 4), 200, 320, fill="white", width=max(3, size // 16))


def draw_clean_door(draw: ImageDraw.ImageDraw, x: int, y: int, w: int, h: int) -> None:
    """Simple clear door — thick frame, minimal detail."""
    stroke = max(14, w // 10)
    draw.rounded_rectangle((x, y, x + w, y + h), radius=8, outline=COLOR, width=stroke)
    inset = stroke + 10
    draw.rectangle((x + inset, y + inset, x + w - inset, y + h - inset), fill=COLOR)
    draw.ellipse((x + w - inset - 28, y + h // 2 - 12, x + w - inset - 8, y + h // 2 + 12), fill="white")


def draw_clean_clipboard(draw: ImageDraw.ImageDraw, x: int, y: int, h: int = 130) -> None:
    """Bold checklist — 3 lines, 2 ticks, readable at small size."""
    w = int(h * 0.72)
    draw.rounded_rectangle((x, y, x + w, y + h), radius=10, fill=COLOR)
    draw.rounded_rectangle((x + 12, y - 16, x + w - 12, y + 4), radius=5, fill=COLOR)
    draw.ellipse((x + w // 2 - 10, y - 26, x + w // 2 + 10, y - 6), outline="white", width=4)
    line_y = y + 28
    for i in range(3):
        draw.rounded_rectangle((x + 14, line_y, x + w - 28, line_y + 10), radius=2, fill="white")
        if i < 2:
            draw.line((x + w - 18, line_y + 1, x + w - 8, line_y + 11), fill="white", width=4)
            draw.line((x + w - 8, line_y + 1, x + w - 20, line_y + 13), fill="white", width=4)
        line_y += 28


def draw_clean_lens(draw: ImageDraw.ImageDraw, cx: int, cy: int, diameter: int = 130) -> None:
    """Single clean magnifying glass — solid ring, no glint gaps."""
    half = diameter // 2
    stroke = max(18, diameter // 8)
    draw.ellipse((cx - half, cy - half, cx + half, cy + half), outline=COLOR, width=stroke)
    hx = cx + int(half * 0.62)
    hy = cy + int(half * 0.62)
    draw.line(
        (hx, hy, hx + int(diameter * 0.42), hy + int(diameter * 0.42)),
        fill=COLOR,
        width=stroke + 4,
    )


def save_hires(name: str, drawer) -> None:
    """Render at 2x resolution then downscale for crisp edges."""
    scale = 2
    big = SIZE * scale
    img = Image.new("RGBA", (big, big), (255, 255, 255, 255))
    draw = ImageDraw.Draw(img)

    def s(v: int) -> int:
        return v * scale

    drawer(draw, s)
    img = img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    OUT.mkdir(parents=True, exist_ok=True)
    path = OUT / f"{name}.png"
    img.convert("RGB").save(path, "PNG", optimize=True)
    print(f"Wrote {path}")


def draw_inspection_items(draw: ImageDraw.ImageDraw, x: int, y: int) -> None:
    """Toolbox, pencil, and tape on ground."""
    # Toolbox
    draw.rounded_rectangle((x, y, x + 72, y + 38), radius=6, fill=COLOR)
    draw.rectangle((x + 30, y - 8, x + 42, y + 6), fill=COLOR)
    # Pencil
    draw.polygon([(x + 88, y + 4), (x + 118, y - 6), (x + 122, y + 2), (x + 92, y + 12)], fill=COLOR)
    draw.polygon([(x + 118, y - 6), (x + 128, y - 4), (x + 122, y + 2)], fill=COLOR)
    # Tape coil
    draw.ellipse((x + 134, y - 2, x + 168, y + 32), outline=COLOR, width=6)
    draw.ellipse((x + 144, y + 8, x + 158, y + 22), fill=COLOR)


def draw_checklist_sheet(draw: ImageDraw.ImageDraw, x: int, y: int, w: int = 56, h: int = 72) -> None:
    """Loose checklist paper on ground."""
    draw.rounded_rectangle((x, y, x + w, y + h), radius=4, fill=COLOR)
    ly = y + 12
    for i in range(4):
        draw.rectangle((x + 8, ly, x + w - 18, ly + 5), fill="white")
        if i < 2:
            draw.line((x + w - 12, ly, x + w - 4, ly + 8), fill="white", width=2)
            draw.line((x + w - 4, ly, x + w - 14, ly + 10), fill="white", width=2)
        ly += 14


def draw_pro_tall_standing(
    draw: ImageDraw.ImageDraw,
    cx: int,
    floor_y: int,
    facing: str = "left",
    with_clipboard: bool = True,
    with_lens: bool = False,
) -> int:
    """Taller standing professional. Returns head_y for positioning extras."""
    head_y = floor_y - 218
    draw_pro_head(draw, cx, head_y, r=30, with_hat=True)
    draw.rounded_rectangle((cx - 38, head_y + 28, cx + 38, floor_y - 54), radius=12, fill=COLOR)
    draw.rectangle((cx - 30, floor_y - 54, cx - 8, floor_y), fill=COLOR)
    draw.rectangle((cx + 8, floor_y - 54, cx + 30, floor_y), fill=COLOR)

    if facing == "left":
        if with_clipboard:
            draw.rectangle((cx - 58, head_y + 58, cx - 20, head_y + 70), fill=COLOR)
            draw_clipboard(draw, cx - 128, head_y + 24, w=68, h=108, checks=3)
        if with_lens:
            draw.line((cx + 16, head_y + 62, cx + 44, head_y + 78), fill=COLOR, width=11)
            draw_lens(draw, cx + 72, head_y + 48, size=48, angle="down-left")
        elif not with_clipboard:
            draw.line((cx + 16, head_y + 62, cx + 50, head_y + 80), fill=COLOR, width=11)
    else:
        if with_clipboard:
            draw.rectangle((cx + 20, head_y + 58, cx + 58, head_y + 70), fill=COLOR)
            draw_clipboard(draw, cx + 60, head_y + 24, w=68, h=108, checks=3)
        if with_lens:
            draw.line((cx - 44, head_y + 78, cx - 16, head_y + 62), fill=COLOR, width=11)
            draw_lens(draw, cx - 72, head_y + 48, size=48, angle="down-right")

    return head_y


def draw_pro_tall_kneeling(
    draw: ImageDraw.ImageDraw,
    cx: int,
    floor_y: int,
    with_lens: bool = True,
) -> int:
    """Taller kneeling professional inspecting low on door."""
    head_y = floor_y - 152
    draw_pro_head(draw, cx, head_y, r=28, with_hat=True)
    draw.rounded_rectangle((cx - 34, head_y + 26, cx + 34, floor_y - 22), radius=10, fill=COLOR)
    draw.polygon([(cx - 34, floor_y - 22), (cx + 8, floor_y - 22), (cx + 38, floor_y), (cx - 10, floor_y)], fill=COLOR)
    draw.polygon([(cx + 8, floor_y - 22), (cx + 36, floor_y - 22), (cx + 78, floor_y - 6), (cx + 42, floor_y - 6)], fill=COLOR)
    draw.line((cx + 22, head_y + 50, cx + 62, head_y + 66), fill=COLOR, width=11)
    if with_lens:
        draw_lens(draw, cx + 88, head_y + 38, size=46, angle="down-right")
    return head_y


def draw_pro_standing_clipboard(draw: ImageDraw.ImageDraw, cx: int, floor_y: int, facing: str = "left") -> None:
    """Standing carpenter with clipboard, facing toward door."""
    head_y = floor_y - 168
    draw_pro_head(draw, cx, head_y, r=26)
    draw.rounded_rectangle((cx - 34, head_y + 24, cx + 34, floor_y - 42), radius=10, fill=COLOR)
    draw.rectangle((cx - 28, floor_y - 42, cx - 6, floor_y), fill=COLOR)
    draw.rectangle((cx + 6, floor_y - 42, cx + 28, floor_y), fill=COLOR)
    if facing == "left":
        draw.rectangle((cx - 54, head_y + 48, cx - 18, head_y + 58), fill=COLOR)
        draw_clipboard(draw, cx - 118, head_y + 20, w=64, h=92, checks=2)
        draw.line((cx + 18, head_y + 56, cx + 52, head_y + 72), fill=COLOR, width=12)
    else:
        draw.rectangle((cx + 18, head_y + 48, cx + 54, head_y + 58), fill=COLOR)
        draw_clipboard(draw, cx + 54, head_y + 20, w=64, h=92, checks=2)
        draw.line((cx - 52, head_y + 72, cx - 18, head_y + 56), fill=COLOR, width=12)


def draw_pro_kneeling_measure(draw: ImageDraw.ImageDraw, cx: int, floor_y: int) -> None:
    """Kneeling professional with measuring tape."""
    head_y = floor_y - 118
    draw_pro_head(draw, cx, head_y, r=24)
    draw.rounded_rectangle((cx - 30, head_y + 22, cx + 30, floor_y - 18), radius=8, fill=COLOR)
    # Kneeling leg + extended leg
    draw.polygon([(cx - 30, floor_y - 18), (cx + 10, floor_y - 18), (cx + 36, floor_y), (cx - 8, floor_y)], fill=COLOR)
    draw.polygon([(cx + 10, floor_y - 18), (cx + 34, floor_y - 18), (cx + 70, floor_y - 4), (cx + 36, floor_y - 4)], fill=COLOR)
    # Arm reaching out
    draw.line((cx + 20, head_y + 44, cx + 58, head_y + 58), fill=COLOR, width=10)


def draw_pro_with_level(draw: ImageDraw.ImageDraw, cx: int, floor_y: int) -> None:
    """Standing pro holding spirit level up to door."""
    head_y = floor_y - 176
    draw_pro_head(draw, cx, head_y, r=26)
    draw.rounded_rectangle((cx - 32, head_y + 24, cx + 32, floor_y - 40), radius=10, fill=COLOR)
    draw.rectangle((cx - 26, floor_y - 40, cx - 4, floor_y), fill=COLOR)
    draw.rectangle((cx + 4, floor_y - 40, cx + 26, floor_y), fill=COLOR)
    # Arms up holding level
    draw.line((cx - 20, head_y + 50, cx - 48, head_y + 34), fill=COLOR, width=10)
    draw.line((cx + 20, head_y + 50, cx + 48, head_y + 34), fill=COLOR, width=10)


def draw_pro_magnifier(draw: ImageDraw.ImageDraw, cx: int, floor_y: int) -> None:
    """Professional leaning in with magnifying glass."""
    head_y = floor_y - 162
    draw_pro_head(draw, cx, head_y, r=25)
    draw.rounded_rectangle((cx - 30, head_y + 22, cx + 30, floor_y - 38), radius=10, fill=COLOR)
    draw.rectangle((cx - 24, floor_y - 38, cx - 4, floor_y), fill=COLOR)
    draw.rectangle((cx + 4, floor_y - 38, cx + 24, floor_y), fill=COLOR)
    # Bent forward posture
    draw.polygon([(cx - 8, head_y + 24), (cx + 24, head_y + 24), (cx + 34, head_y + 52), (cx - 2, head_y + 52)], fill=COLOR)
    # Magnifier in hand
    draw.ellipse((cx + 36, head_y + 10, cx + 86, head_y + 60), outline=COLOR, width=12)
    draw.line((cx + 78, head_y + 52, cx + 108, head_y + 82), fill=COLOR, width=14)


def sample_a_door_measure(draw: ImageDraw.ImageDraw) -> None:
    """Door opening with measuring tape — site measurement."""
    draw_door_frame(draw, 156, 100, 200, 300)
    # Measuring tape arc across door
    draw.arc((120, 180, 392, 340), 200, 340, fill=COLOR, width=14)
    for i, deg in enumerate(range(210, 331, 24)):
        import math
        rad = math.radians(deg)
        cx, cy = 256, 260
        r1, r2 = 118, 132
        x1 = cx + r1 * math.cos(rad)
        y1 = cy + r1 * math.sin(rad)
        x2 = cx + r2 * math.cos(rad)
        y2 = cy + r2 * math.sin(rad)
        draw.line((x1, y1, x2, y2), fill=COLOR, width=4 if i % 2 == 0 else 2)
    # Tape case
    draw.rounded_rectangle((90, 300, 150, 370), radius=8, fill=COLOR)
    draw.ellipse((105, 315, 135, 345), outline="white", width=4)


def sample_b_door_clipboard(draw: ImageDraw.ImageDraw) -> None:
    """Door + inspection checklist clipboard."""
    draw_door_frame(draw, 110, 120, 170, 260)
    # Clipboard
    draw.rounded_rectangle((300, 100, 410, 380), radius=12, fill=COLOR)
    draw.rounded_rectangle((318, 88, 392, 118), radius=6, fill=COLOR)
    draw.ellipse((345, 78, 365, 98), outline="white", width=4)
    for y, w in ((150, 60), (190, 50), (230, 55), (270, 45), (310, 50)):
        draw.rectangle((320, y, 320 + w, y + 10), fill="white")
    # Check marks on first two lines
    draw.line((388, 152, 398, 162), fill="white", width=5)
    draw.line((398, 152, 378, 172), fill="white", width=5)
    draw.line((388, 192, 398, 202), fill="white", width=5)
    draw.line((398, 192, 378, 212), fill="white", width=5)


def sample_c_door_level(draw: ImageDraw.ImageDraw) -> None:
    """Spirit level against door — alignment check."""
    draw_door_frame(draw, 170, 90, 172, 290)
    # Spirit level
    draw.rounded_rectangle((80, 200, 432, 280), radius=28, fill=COLOR)
    draw.ellipse((196, 218, 256, 262), outline="white", width=6)
    draw.ellipse((216, 232, 236, 248), fill="white")
    draw.rectangle((280, 228, 380, 252), fill="white")
    # Bubble centered = level
    draw.ellipse((318, 234, 342, 246), fill=COLOR)


def sample_d_carpenter_visit(draw: ImageDraw.ImageDraw) -> None:
    """Hard hat + door — carpenter site visit."""
    # Hard hat
    draw.polygon([(196, 95), (316, 95), (332, 140), (180, 140)], fill=COLOR)
    draw.rectangle((210, 140, 302, 158), fill=COLOR)
    # Door
    draw_door_frame(draw, 186, 170, 140, 220)
    # Toolbox at bottom
    draw.rounded_rectangle((130, 400, 382, 460), radius=10, fill=COLOR)
    draw.rectangle((248, 400, 264, 420), fill="white", width=3)
    # Wrench hint
    draw.polygon([(360, 360), (400, 340), (415, 360), (375, 380)], fill=COLOR)


def sample_e_door_magnifier(draw: ImageDraw.ImageDraw) -> None:
    """Magnifying glass over door frame — detailed inspection."""
    draw_door_frame(draw, 140, 110, 180, 280)
    # Magnifying glass
    draw.ellipse((280, 150, 400, 270), outline=COLOR, width=18)
    draw.line((378, 258, 430, 340), fill=COLOR, width=20)
    # Highlight detail lines inside lens (frame gaps)
    draw.line((310, 200, 370, 200), fill=COLOR, width=6)
    draw.line((310, 230, 350, 230), fill=COLOR, width=6)


def sample_f_home_pin(draw: ImageDraw.ImageDraw) -> None:
    """House with door + location pin — on-site booking."""
    # House body
    draw.polygon([(256, 80), (400, 180), (400, 380), (112, 380), (112, 180)], fill=COLOR)
    draw.rectangle((140, 200, 372, 380), fill="white")
    # Door in house
    draw.rounded_rectangle((210, 250, 302, 380), radius=6, fill=COLOR)
    draw.ellipse((268, 310, 288, 330), fill="white")
    # Location pin overlapping top-right
    draw.ellipse((310, 110, 380, 180), fill=COLOR)
    draw.polygon([(345, 180), (310, 250), (380, 250)], fill=COLOR)
    draw.ellipse((330, 130, 360, 160), fill="white")


def sample_g_pro_clipboard(draw: ImageDraw.ImageDraw) -> None:
    """Professional with clipboard inspecting door."""
    draw_door_frame(draw, 70, 110, 160, 280)
    draw_pro_standing_clipboard(draw, 340, 400, facing="left")


def sample_h_pro_measure(draw: ImageDraw.ImageDraw) -> None:
    """Professional kneeling, measuring door opening."""
    draw_door_frame(draw, 280, 100, 170, 290)
    draw_pro_kneeling_measure(draw, 150, 400)
    # Tape extended to door
    draw.line((208, 360, 280, 340), fill=COLOR, width=8)
    for x in range(210, 278, 16):
        draw.line((x, 334, x, 346), fill=COLOR, width=3)
    draw.rounded_rectangle((188, 348, 228, 378), radius=6, fill=COLOR)


def sample_i_pro_level(draw: ImageDraw.ImageDraw) -> None:
    """Professional checking door alignment with spirit level."""
    draw_door_frame(draw, 70, 90, 150, 280)
    draw_pro_with_level(draw, 360, 400)
    draw.rounded_rectangle((118, 210, 318, 252), radius=18, fill=COLOR)
    draw.ellipse((168, 220, 208, 242), outline="white", width=5)
    draw.ellipse((182, 226, 194, 236), fill="white")
    draw.rectangle((228, 224, 288, 238), fill="white")
    draw.ellipse((248, 226, 268, 236), fill=COLOR)


def sample_j_pro_magnifier(draw: ImageDraw.ImageDraw) -> None:
    """Professional closely inspecting door frame."""
    draw_door_frame(draw, 300, 100, 150, 280)
    draw_pro_magnifier(draw, 170, 400)
    # Frame detail lines visible through magnifier area
    draw.line((318, 150, 318, 360), fill="white", width=4)
    draw.line((300, 180, 450, 180), fill="white", width=3)


def sample_k_pro_clipboard_wide(draw: ImageDraw.ImageDraw) -> None:
    """Wide scene: door + professional taking notes."""
    draw_door_frame(draw, 100, 80, 140, 260)
    draw_pro_standing_clipboard(draw, 330, 370, facing="left")
    # Small inspection badge / check icon near pro
    draw.ellipse((390, 300, 430, 340), fill=COLOR)
    draw.line((400, 318, 408, 326), fill="white", width=4)
    draw.line((408, 318, 396, 332), fill="white", width=4)


def sample_l_tall_clipboard_lens(draw: ImageDraw.ImageDraw) -> None:
    """Tall pro: clipboard + lens + items (like #7 enhanced)."""
    draw_door_frame(draw, 58, 70, 150, 300)
    draw_pro_tall_standing(draw, 350, 430, facing="left", with_clipboard=True, with_lens=True)
    draw_inspection_items(draw, 250, 392)
    draw_lens(draw, 118, 200, size=40, angle="down-right")


def sample_m_tall_kneel_lens(draw: ImageDraw.ImageDraw) -> None:
    """Tall kneeling pro: lens at door + tape + checklist (like #8 enhanced)."""
    draw_door_frame(draw, 268, 70, 165, 300)
    draw_pro_tall_kneeling(draw, 140, 430, with_lens=True)
    draw.line((198, 388, 268, 368), fill=COLOR, width=8)
    for x in range(200, 266, 14):
        draw.line((x, 362, x, 374), fill=COLOR, width=3)
    draw_checklist_sheet(draw, 58, 360)
    draw_inspection_items(draw, 168, 396)


def sample_n_tall_full_kit(draw: ImageDraw.ImageDraw) -> None:
    """Tall pro with lens, clipboard, and all inspection items."""
    draw_door_frame(draw, 52, 80, 138, 290)
    head_y = draw_pro_tall_standing(draw, 358, 430, facing="left", with_clipboard=True, with_lens=True)
    draw_inspection_items(draw, 228, 388)
    draw_checklist_sheet(draw, 300, 368)
    draw_lens(draw, 108, 170, size=52, angle="down-right")
    # Lens lines on door frame detail
    draw.line((98, 120, 98, 340), fill="white", width=3)


def sample_o_tall_lens_focus(draw: ImageDraw.ImageDraw) -> None:
    """Tall pro holding large lens up to door hinge area."""
    draw_door_frame(draw, 78, 60, 155, 310)
    draw_pro_tall_standing(draw, 370, 430, facing="left", with_clipboard=False, with_lens=True)
    draw_clipboard(draw, 300, 300, w=60, h=88, checks=2)
    draw_lens(draw, 195, 195, size=58, angle="down-left")
    draw_inspection_items(draw, 248, 394)


def sample_p_tall_measure_lens(draw: ImageDraw.ImageDraw) -> None:
    """Tall kneeling: lens in one hand, measuring tape to door."""
    draw_door_frame(draw, 285, 65, 160, 305)
    draw_pro_tall_kneeling(draw, 130, 430, with_lens=True)
    draw.line((210, 385, 285, 362), fill=COLOR, width=9)
    for x in range(212, 282, 15):
        draw.line((x, 356, x, 368), fill=COLOR, width=3)
    draw_rounded_rect = draw.rounded_rectangle
    draw_rounded_rect((192, 372, 232, 402), radius=6, fill=COLOR)
    draw_checklist_sheet(draw, 48, 355)
    draw_inspection_items(draw, 100, 398)


def sample_q_tall_checklist_scene(draw: ImageDraw.ImageDraw) -> None:
    """Tall pro reviewing checklist with lens toward door."""
    draw_door_frame(draw, 64, 75, 148, 295)
    draw_pro_tall_standing(draw, 345, 430, facing="left", with_clipboard=True, with_lens=True)
    draw_checklist_sheet(draw, 268, 340)
    draw_inspection_items(draw, 188, 390)
    draw_lens(draw, 130, 220, size=44, angle="down-right")


def _draw_door_scaled(draw, s, x, y, w, h) -> None:
    draw.rounded_rectangle((s(x), s(y), s(x + w), s(y + h)), radius=s(10), outline=COLOR, width=s(16))
    draw.rectangle((s(x + 18), s(y + 18), s(x + w - 18), s(y + h - 18)), fill=COLOR)
    draw.ellipse((s(x + w - 70), s(y + h // 2 - 15), s(x + w - 40), s(y + h // 2 + 15)), fill="white")
    draw.line((s(x + w // 2), s(y + 18), s(x + w // 2), s(y + h - 18)), fill="white", width=s(5))


def _draw_clipboard_scaled(draw, s, x, y, w, h, checks=2) -> None:
    draw.rounded_rectangle((s(x), s(y), s(x + w), s(y + h)), radius=s(10), fill=COLOR)
    draw.rounded_rectangle((s(x + 10), s(y - 14), s(x + w - 10), s(y + 6)), radius=s(4), fill=COLOR)
    draw.ellipse((s(x + w // 2 - 8), s(y - 22), s(x + w // 2 + 8), s(y - 6)), outline="white", width=s(3))
    line_y = y + 18
    for i in range(4):
        lw = w - 24 - (i % 2) * 8
        draw.rectangle((s(x + 12), s(line_y), s(x + 12 + lw), s(line_y + 10)), fill="white")
        if i < checks:
            draw.line((s(x + w - 14), s(line_y + 1), s(x + w - 6), s(line_y + 9)), fill="white", width=s(3))
            draw.line((s(x + w - 6), s(line_y + 1), s(x + w - 18), s(line_y + 13)), fill="white", width=s(3))
        line_y += 18


def _draw_lens_scaled(draw, s, cx, cy, diameter) -> None:
    half = diameter // 2
    stroke = max(18, diameter // 8)
    draw.ellipse((s(cx - half), s(cy - half), s(cx + half), s(cy + half)), outline=COLOR, width=s(stroke))
    hx = cx + int(half * 0.62)
    hy = cy + int(half * 0.62)
    draw.line(
        (s(hx), s(hy), s(hx + int(diameter * 0.42)), s(hy + int(diameter * 0.42))),
        fill=COLOR,
        width=s(stroke + 4),
    )


def compose_inspect_v1(draw, s) -> None:
    """Like #02 layout + lens below — door left, checklist right, lens bottom."""
    _draw_door_scaled(draw, s, 108, 108, 158, 250)
    _draw_clipboard_scaled(draw, s, 296, 96, 82, 118, checks=2)
    _draw_lens_scaled(draw, s, 220, 368, 108)


def compose_inspect_v2(draw, s) -> None:
    """Door left, big lens center, checklist top-right."""
    _draw_door_scaled(draw, s, 96, 118, 150, 248)
    _draw_clipboard_scaled(draw, s, 318, 108, 80, 116, checks=2)
    _draw_lens_scaled(draw, s, 268, 310, 124)


def compose_inspect_v3(draw, s) -> None:
    """Door + checklist side by side (clear), lens tucked under door."""
    _draw_door_scaled(draw, s, 112, 100, 162, 268)
    _draw_clipboard_scaled(draw, s, 304, 100, 84, 122, checks=2)
    _draw_lens_scaled(draw, s, 196, 392, 100)


def compose_inspect_v4(draw, s) -> None:
    """Three columns — door | lens | checklist, zero overlap."""
    _draw_door_scaled(draw, s, 48, 118, 128, 240)
    _draw_lens_scaled(draw, s, 232, 256, 96)
    _draw_clipboard_scaled(draw, s, 318, 118, 78, 118, checks=2)


SAMPLES: dict[str, tuple[str, callable]] = {
    "01-door-measure": ("Door + measuring tape", sample_a_door_measure),
    "02-door-clipboard": ("Door + inspection checklist", sample_b_door_clipboard),
    "03-door-level": ("Door + spirit level", sample_c_door_level),
    "04-carpenter-visit": ("Hard hat + door + toolbox", sample_d_carpenter_visit),
    "05-door-magnifier": ("Door + magnifying glass", sample_e_door_magnifier),
    "06-home-pin": ("House + door + location pin", sample_f_home_pin),
    "07-pro-clipboard": ("Professional + clipboard + door", sample_g_pro_clipboard),
    "08-pro-measure": ("Professional kneeling + measuring door", sample_h_pro_measure),
    "09-pro-level": ("Professional + spirit level at door", sample_i_pro_level),
    "10-pro-magnifier": ("Professional inspecting door closely", sample_j_pro_magnifier),
    "11-pro-notes": ("Professional taking inspection notes", sample_k_pro_clipboard_wide),
    "12-tall-clipboard-lens": ("Tall pro + clipboard + lens + items", sample_l_tall_clipboard_lens),
    "13-tall-kneel-lens": ("Tall kneeling + lens + tape + checklist", sample_m_tall_kneel_lens),
    "14-tall-full-kit": ("Tall pro + lens + clipboard + all items", sample_n_tall_full_kit),
    "15-tall-lens-focus": ("Tall pro + large lens at door + items", sample_o_tall_lens_focus),
    "16-tall-measure-lens": ("Tall kneeling + lens + measuring tape", sample_p_tall_measure_lens),
    "17-tall-checklist-scene": ("Tall pro + checklist + lens + items", sample_q_tall_checklist_scene),
    "18-inspect-door-lens": ("Door + checklist + lens below", compose_inspect_v1),
    "19-inspect-lens-door": ("Door + big lens + checklist", compose_inspect_v2),
    "20-inspect-checklist": ("Door + checklist + lens under door", compose_inspect_v3),
    "21-inspect-three-col": ("Door | lens | checklist — no overlap", compose_inspect_v4),
}


def main() -> None:
    import sys

    only_pro = "--pro-only" in sys.argv
    only_tall = "--tall-only" in sys.argv
    only_inspect = "--inspect-only" in sys.argv
    for slug, (_, drawer) in SAMPLES.items():
        if only_pro and "-pro-" not in slug:
            continue
        if only_tall and "tall-" not in slug:
            continue
        if only_inspect and "inspect-" not in slug:
            continue
        if only_inspect:
            save_hires(slug, drawer)
            continue
        img, draw = canvas()
        drawer(draw)
        save(slug, img)


if __name__ == "__main__":
    main()
