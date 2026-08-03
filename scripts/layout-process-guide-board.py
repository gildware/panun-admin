#!/usr/bin/env python3
"""Re-layout process guide board: non-overlapping top-down tiers, orthogonal connectors."""

from __future__ import annotations

import json
import sys
from collections import defaultdict
from pathlib import Path

BOARD_PATH = Path(__file__).resolve().parents[1] / 'public/assets/admin-module/process-guide/miro-board.json'

PAD_X = 1200
PAD_Y = 1400
MAX_PER_ROW = 4
ORIGIN_X = 0
ORIGIN_Y = 2000
TOP_Y_THRESHOLD = 5000


def is_artifact(shape: dict) -> bool:
    text = (shape.get('text') or '').strip()
    if shape.get('shape') == 'rhombus' and not text:
        return True
    if shape.get('w', 0) < 120 and shape.get('h', 0) < 120 and not text:
        return True
    return False


def bbox(shape: dict) -> tuple[float, float, float, float]:
    return (
        shape['x'] - shape['w'] / 2,
        shape['y'] - shape['h'] / 2,
        shape['x'] + shape['w'] / 2,
        shape['y'] + shape['h'] / 2,
    )


def boxes_overlap(a: dict, b: dict, pad_x: float = 0, pad_y: float = 0) -> bool:
    ax0, ay0, ax1, ay1 = bbox(a)
    bx0, by0, bx1, by1 = bbox(b)
    return not (
        ax1 + pad_x <= bx0
        or bx1 + pad_x <= ax0
        or ay1 + pad_y <= by0
        or by1 + pad_y <= ay0
    )


def assign_levels(shapes: dict[str, dict], edges: list[tuple[str, str]]) -> dict[str, int]:
    indeg: dict[str, int] = defaultdict(int)
    for a, b in edges:
        indeg[b] += 1

    level: dict[str, int] = {}
    for sid, s in shapes.items():
        if s['y'] < TOP_Y_THRESHOLD or indeg[sid] == 0:
            level[sid] = 0

    if not level:
        level = {sid: 0 for sid in shapes}

    for _ in range(len(shapes) + 5):
        updated = False
        for a, b in edges:
            if a not in level:
                continue
            nl = level[a] + 1
            if b not in level or level[b] < nl:
                level[b] = nl
                updated = True
        if not updated:
            break

    for sid in shapes:
        if sid not in level:
            level[sid] = max(level.values()) + 1 if level else 0

    return level


def order_row(nodes: list[str], shapes: dict, edges: list[tuple[str, str]]) -> list[str]:
    preds: dict[str, list[str]] = defaultdict(list)
    for a, b in edges:
        if b in nodes and a in shapes:
            preds[b].append(a)

    nodes = sorted(nodes, key=lambda sid: shapes[sid]['x'])
    for _ in range(4):
        scored = []
        for sid in nodes:
            px = [shapes[p]['x'] for p in preds[sid] if p in shapes]
            bc = sum(px) / len(px) if px else shapes[sid]['x']
            scored.append((bc, sid))
        nodes = [sid for _, sid in sorted(scored)]
    return nodes


def split_into_rows(nodes: list[str], shapes: dict) -> list[list[str]]:
    rows: list[list[str]] = []
    current: list[str] = []
    current_w = 0.0
    limit_w = 42000.0

    for sid in nodes:
        w = shapes[sid]['w']
        if w > limit_w * 0.65:
            if current:
                rows.append(current)
                current = []
                current_w = 0.0
            rows.append([sid])
            continue
        extra = w + (PAD_X if current else 0)
        if current and (current_w + extra > limit_w or len(current) >= MAX_PER_ROW):
            rows.append(current)
            current = []
            current_w = 0.0
        current.append(sid)
        current_w += w + (PAD_X if len(current) > 1 else 0)

    if current:
        rows.append(current)
    return rows


def place_row(nodes: list[str], shapes: dict, top_y: float) -> float:
    if not nodes:
        return top_y
    max_h = max(shapes[sid]['h'] for sid in nodes)
    center_y = top_y + max_h / 2
    total_w = sum(shapes[sid]['w'] for sid in nodes) + PAD_X * max(0, len(nodes) - 1)
    x_left = ORIGIN_X - total_w / 2
    for sid in nodes:
        s = shapes[sid]
        s['x'] = x_left + s['w'] / 2
        s['y'] = center_y
        x_left += s['w'] + PAD_X
    return top_y + max_h


def resolve_overlaps(shapes: dict[str, dict], max_passes: int = 200) -> None:
    ids = list(shapes.keys())
    for _ in range(max_passes):
        moved = False
        for i in range(len(ids)):
            for j in range(i + 1, len(ids)):
                a = shapes[ids[i]]
                b = shapes[ids[j]]
                if not boxes_overlap(a, b, PAD_X * 0.5, PAD_Y * 0.5):
                    continue
                ax0, ay0, ax1, ay1 = bbox(a)
                bx0, by0, bx1, by1 = bbox(b)
                overlap_x = min(ax1, bx1) - max(ax0, bx0)
                overlap_y = min(ay1, by1) - max(ay0, by0)
                if overlap_y <= overlap_x:
                    shift = overlap_y + PAD_Y
                    if b['y'] >= a['y']:
                        b['y'] += shift
                    else:
                        b['y'] -= shift
                else:
                    shift = overlap_x + PAD_X
                    if b['x'] >= a['x']:
                        b['x'] += shift
                    else:
                        b['x'] -= shift
                moved = True
        if not moved:
            break


def count_overlaps(shapes: dict[str, dict]) -> int:
    ids = list(shapes.keys())
    n = 0
    for i in range(len(ids)):
        for j in range(i + 1, len(ids)):
            if boxes_overlap(shapes[ids[i]], shapes[ids[j]]):
                n += 1
    return n


def layout_board(board: dict) -> dict:
    shapes_list = board['shapes']
    shapes = {s['id']: s for s in shapes_list if not is_artifact(s)}
    edges = [(c['from'], c['to']) for c in board['connectors'] if c['from'] in shapes and c['to'] in shapes]

    level = assign_levels(shapes, edges)
    tiers: dict[int, list[str]] = defaultdict(list)
    for sid, lv in level.items():
        tiers[lv].append(sid)

    y_top = ORIGIN_Y
    for lv in sorted(tiers):
        nodes = order_row(tiers[lv], shapes, edges)
        rows = split_into_rows(nodes, shapes)
        tier_start = y_top
        for row in rows:
            y_top = place_row(row, shapes, y_top)
            y_top += PAD_Y
        y_top = tier_start + max(y_top - tier_start, max(shapes[sid]['h'] for sid in nodes) + PAD_Y)

    resolve_overlaps(shapes)

    for s in shapes_list:
        if not is_artifact(s):
            continue
        attached = None
        for c in board['connectors']:
            if c['to'] == s['id'] and c['from'] in shapes:
                attached = shapes[c['from']]
                break
            if c['from'] == s['id'] and c['to'] in shapes:
                attached = shapes[c['to']]
                break
        if attached:
            s['x'] = attached['x']
            s['y'] = attached['y'] - attached['h'] / 2 - s.get('h', 80) / 2 - PAD_Y * 0.5

    all_main = {s['id']: s for s in shapes_list if not is_artifact(s)}
    for s in shapes_list:
        if is_artifact(s):
            for other in all_main.values():
                if boxes_overlap(s, other, PAD_X * 0.25, PAD_Y * 0.25):
                    s['y'] = other['y'] - other['h'] / 2 - s.get('h', 80) / 2 - PAD_Y * 0.5

    shape_map = {s['id']: s for s in shapes_list}
    for label in board.get('labels', []):
        best = None
        best_d = float('inf')
        for c in board['connectors']:
            fa, tb = shape_map.get(c['from']), shape_map.get(c['to'])
            if not fa or not tb:
                continue
            mx = (fa['x'] + tb['x']) / 2
            my = (fa['y'] + fa['h'] / 2 + tb['y'] - tb['h'] / 2) / 2
            d = abs(label['x'] - mx) + abs(label['y'] - my)
            if d < best_d:
                best_d = d
                best = (fa, tb)
        if best:
            fa, tb = best
            label['x'] = (fa['x'] + tb['x']) / 2
            label['y'] = (fa['y'] + fa['h'] / 2 + tb['y'] - tb['h'] / 2) / 2 - 350

    for c in board['connectors']:
        c['curve'] = 'orthogonal'
        c['startSnap'] = 'bottom'
        c['endSnap'] = 'top'

    xs, ys = [], []
    for s in shapes_list:
        xs.extend([s['x'] - s['w'] / 2, s['x'] + s['w'] / 2])
        ys.extend([s['y'] - s['h'] / 2, s['y'] + s['h'] / 2])
    for label in board.get('labels', []):
        xs.append(label['x'])
        ys.append(label['y'])
    pad = 3000
    board['bounds'] = {
        'minX': min(xs) - pad,
        'minY': min(ys) - pad,
        'maxX': max(xs) + pad,
        'maxY': max(ys) + pad,
    }

    return board


def main() -> int:
    path = Path(sys.argv[1]) if len(sys.argv) > 1 else BOARD_PATH
    board = json.loads(path.read_text())
    board = layout_board(board)

    shapes = {s['id']: s for s in board['shapes'] if not is_artifact(s)}
    overlaps = count_overlaps(shapes)

    path.write_text(json.dumps(board, ensure_ascii=False, separators=(',', ':')))
    print(f'Layout complete: {len(board["shapes"])} shapes, {overlaps} overlaps remaining -> {path}')
    if overlaps:
        return 1
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
