#!/usr/bin/env python3
"""Build miro-board.json from a Miro board export.

Preferred: Miro MCP layout_read → scripts/sync-miro-board-from-dsl.py
  1. In Cursor, run layout_read on the board URL
  2. Save the JSON response to scripts/miro-layout-read.json
  3. python3 scripts/sync-miro-board-from-dsl.py
     — or: php artisan process-guide:sync-miro-board

Legacy CDP export (miro.board.get()):
  1. Open the Miro board in browser while logged in / view access
  2. Run CDP: miro.board.get().then(items => items)
  3. Save result to a JSON file
  4. python3 scripts/export-miro-board.py /path/to/cdp-export.json

Output: public/assets/admin-module/process-guide/miro-board.json
"""
import json
import re
import sys
from html import unescape
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'public/assets/admin-module/process-guide/miro-board.json'


def strip_html(s):
    s = re.sub(r'<br\s*/?>', '\n', s or '', flags=re.I)
    s = re.sub(r'<[^>]+>', ' ', s)
    s = unescape(s)
    return re.sub(r'[ \t]+', ' ', s).replace('\n ', '\n').strip()


def ep_item(ep):
    if isinstance(ep, dict):
        return ep.get('item')
    return ep


def load_items(path):
    data = json.loads(Path(path).read_text())
    val = data
    while isinstance(val, dict):
        if 'value' in val and isinstance(val['value'], list):
            return val['value']
        if 'result' in val:
            val = val['result']
        else:
            break
    if isinstance(val, list):
        return val
    raise SystemExit('Could not parse Miro export')


def main():
    src = sys.argv[1] if len(sys.argv) > 1 else None
    if not src:
        raise SystemExit('Provide path to CDP export JSON')
    val = load_items(src)
    shapes, connectors, labels = [], [], []
    for i in val:
        t = i.get('type')
        if t == 'shape':
            st = i.get('style') or {}
            shapes.append({
                'id': i['id'],
                'x': i.get('x', 0),
                'y': i.get('y', 0),
                'w': i.get('width') or 1000,
                'h': i.get('height') or 600,
                'rotation': i.get('rotation') or 0,
                'shape': i.get('shape') or 'rectangle',
                'text': strip_html(i.get('content', '')),
                'fill': st.get('fillColor', '#ffffff'),
                'fillOpacity': st.get('fillOpacity', 1),
                'stroke': st.get('borderColor', '#1a1a1a'),
                'strokeWidth': st.get('borderWidth', 2),
                'color': st.get('color', '#1a1a1a'),
                'fontSize': st.get('fontSize', 200),
            })
        elif t == 'text':
            text = strip_html(i.get('content', ''))
            if text:
                labels.append({'id': i['id'], 'x': i.get('x', 0), 'y': i.get('y', 0), 'text': text})
        elif t == 'connector':
            st = i.get('style') or {}
            start, end = i.get('start') or {}, i.get('end') or {}
            connectors.append({
                'id': i['id'],
                'from': ep_item(start),
                'to': ep_item(end),
                'startPos': start.get('position'),
                'endPos': end.get('position'),
                'startSnap': start.get('snapTo'),
                'endSnap': end.get('snapTo'),
                'stroke': st.get('strokeColor', '#333333'),
                'strokeWidth': st.get('strokeWidth', 24),
                'curve': i.get('shape', 'curved'),
            })
    pad = 8000
    xs, ys = [], []
    for s in shapes:
        xs.extend([s['x'] - s['w'] / 2, s['x'] + s['w'] / 2])
        ys.extend([s['y'] - s['h'] / 2, s['y'] + s['h'] / 2])
    for l in labels:
        xs.append(l['x'])
        ys.append(l['y'])
    board = {
        'title': 'Lead Qualification Flow',
        'bounds': {'minX': min(xs) - pad, 'minY': min(ys) - pad, 'maxX': max(xs) + pad, 'maxY': max(ys) + pad},
        'shapes': shapes,
        'connectors': connectors,
        'labels': labels,
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(board, ensure_ascii=False))
    print(f'Wrote {OUT} ({len(shapes)} shapes, {len(connectors)} connectors)')


if __name__ == '__main__':
    main()
