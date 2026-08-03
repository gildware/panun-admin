#!/usr/bin/env python3
"""Build miro-board.json from Miro MCP layout_read DSL output.

Usage:
  # From MCP JSON response ({"success": true, "dsl": "..."})
  python3 scripts/sync-miro-board-from-dsl.py scripts/miro-layout-read.json

  # From raw DSL text
  python3 scripts/sync-miro-board-from-dsl.py --raw scripts/miro-layout.dsl

Output: public/assets/admin-module/process-guide/miro-board.json
"""
import json
import re
import sys
from html import unescape
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'public/assets/admin-module/process-guide/miro-board.json'
WIDGET_ID = re.compile(r'moveToWidget=(\d+)')
LINE = re.compile(r'^(\S+)\s+(SHAPE|TEXT|CONNECTOR)\s+(.*)$')
KV = re.compile(r'(\w+)=([^\s"]+)')
QUOTED = re.compile(r'\s"((?:[^"\\]|\\.)*)"\s*$')


def strip_html(s):
    s = re.sub(r'<br\s*/?>', '\n', s or '', flags=re.I)
    s = re.sub(r'<[^>]+>', ' ', s)
    s = unescape(s)
    return re.sub(r'[ \t]+', ' ', s).replace('\n ', '\n').strip()


def widget_id(url):
    m = WIDGET_ID.search(url or '')
    return m.group(1) if m else None


def parse_attrs(rest):
    text = ''
    m = QUOTED.search(rest)
    if m:
        text = m.group(1)
        rest = rest[:m.start()].strip()
    attrs = {k: v for k, v in KV.findall(rest)}
    return attrs, text


def load_dsl(path, raw=False):
    text = Path(path).read_text()
    if raw:
        return text
    data = json.loads(text)
    if isinstance(data, dict) and 'dsl' in data:
        return data['dsl']
    if isinstance(data, str):
        return data
    raise SystemExit(f'Could not find DSL in {path}')


def parse_dsl(dsl):
    shapes, connectors, labels = [], [], []
    for line in dsl.splitlines():
        line = line.strip()
        if not line:
            continue
        m = LINE.match(line)
        if not m:
            continue
        url, kind, rest = m.groups()
        wid = widget_id(url)
        attrs, text = parse_attrs(rest)

        if kind == 'SHAPE':
            fill = attrs.get('fill', '#ffffff')
            fill_opacity = float(attrs.get('fill_opacity', '1') or '1')
            if fill_opacity == 0.0:
                fill = 'transparent'
            shapes.append({
                'id': wid,
                'x': float(attrs.get('x', 0)),
                'y': float(attrs.get('y', 0)),
                'w': float(attrs.get('w', 1000)),
                'h': float(attrs.get('h', 600)),
                'rotation': 0,
                'shape': attrs.get('type', 'rectangle'),
                'text': strip_html(text),
                'fill': fill,
                'fillOpacity': fill_opacity,
                'stroke': attrs.get('border_color', '#1a1a1a'),
                'strokeWidth': float(attrs.get('border_width', '2') or '2'),
                'color': attrs.get('color', '#1a1a1a'),
                'fontSize': float(attrs.get('size', '200') or '200'),
            })
        elif kind == 'TEXT':
            plain = strip_html(text)
            if plain:
                labels.append({
                    'id': wid,
                    'x': float(attrs.get('x', 0)),
                    'y': float(attrs.get('y', 0)),
                    'text': plain,
                    'fontSize': float(attrs.get('size', '900') or '900'),
                })
        elif kind == 'CONNECTOR':
            connectors.append({
                'id': wid,
                'from': widget_id(attrs.get('from')),
                'to': widget_id(attrs.get('to')),
                'startPos': None,
                'endPos': None,
                'startSnap': 'auto',
                'endSnap': 'auto',
                'stroke': attrs.get('stroke_color', '#333333'),
                'strokeWidth': float(attrs.get('stroke_width', '24') or '24'),
                'curve': attrs.get('shape', 'curved'),
            })
    return shapes, connectors, labels


def build_board(shapes, connectors, labels, title='Lead Qualification Flow'):
    pad = 8000
    xs, ys = [], []
    for s in shapes:
        xs.extend([s['x'] - s['w'] / 2, s['x'] + s['w'] / 2])
        ys.extend([s['y'] - s['h'] / 2, s['y'] + s['h'] / 2])
    for l in labels:
        xs.append(l['x'])
        ys.append(l['y'])
    if not xs:
        raise SystemExit('No shapes or labels found in DSL')
    return {
        'title': title,
        'bounds': {
            'minX': min(xs) - pad,
            'minY': min(ys) - pad,
            'maxX': max(xs) + pad,
            'maxY': max(ys) + pad,
        },
        'shapes': shapes,
        'connectors': connectors,
        'labels': labels,
    }


def main():
    args = sys.argv[1:]
    raw = False
    if args and args[0] == '--raw':
        raw = True
        args = args[1:]
    src = args[0] if args else ROOT / 'scripts/miro-layout-read.json'
    dsl = load_dsl(src, raw=raw)
    shapes, connectors, labels = parse_dsl(dsl)
    board = build_board(shapes, connectors, labels)
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(board, ensure_ascii=False))
    print(f'Wrote {OUT} ({len(shapes)} shapes, {len(connectors)} connectors, {len(labels)} labels)')


if __name__ == '__main__':
    main()
