#!/usr/bin/env python3
"""Generate Book-Kaergar-style variant icons via Gemini Flash Image, force navy #1A233A 512px."""

from __future__ import annotations

import base64
import json
import os
import sys
import time
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path

from PIL import Image
from io import BytesIO

PROMPTS = Path(os.environ.get("PROMPTS", "/tmp/pk-fourcat-icon-prompts.json"))
RAW_DIR = Path(os.environ.get("RAW_DIR", "/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets"))
OUT_DIR = Path(
    os.environ.get(
        "OUT_DIR",
        "/Users/kamran/Desktop/panun kaergar/panun-admin/scripts/assets/variant-icons",
    )
)
MODEL = os.environ.get("GEMINI_IMAGE_MODEL", "gemini-2.5-flash-image")
WORKERS = int(os.environ.get("WORKERS", "4"))
LIMIT = int(os.environ.get("LIMIT", "0"))
OFFSET = int(os.environ.get("OFFSET", "0"))
SKIP_EXISTING = os.environ.get("SKIP_EXISTING", "1") != "0"

BRAND = (0x1A, 0x23, 0x3A, 255)
SIZE = 512
PAD = 0.10


def load_api_key() -> str:
    key = os.environ.get("GEMINI_API_KEY", "").strip()
    if key:
        return key
    env_path = Path("/Users/kamran/Desktop/panun kaergar/panun-admin/.env")
    for line in env_path.read_text().splitlines():
        if line.startswith("GEMINI_API_KEY="):
            return line.split("=", 1)[1].strip().strip('"').strip("'")
    raise SystemExit("GEMINI_API_KEY missing")


def force_navy(img: Image.Image) -> Image.Image:
    img = img.convert("RGBA")
    px = img.load()
    w, h = img.size
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            if a < 20 or (r > 230 and g > 230 and b > 230):
                px[x, y] = (0, 0, 0, 0)
            else:
                px[x, y] = BRAND
    return img


def crop_center(img: Image.Image) -> Image.Image:
    alpha = img.split()[-1]
    bbox = alpha.getbbox()
    if not bbox:
        return img.resize((SIZE, SIZE), Image.Resampling.LANCZOS)
    cropped = img.crop(bbox)
    cw, ch = cropped.size
    side = max(cw, ch)
    pad = int(side * PAD)
    canvas = Image.new("RGBA", (side + 2 * pad, side + 2 * pad), (0, 0, 0, 0))
    ox = (canvas.size[0] - cw) // 2
    oy = (canvas.size[1] - ch) // 2
    canvas.paste(cropped, (ox, oy), cropped)
    return canvas.resize((SIZE, SIZE), Image.Resampling.LANCZOS)


def generate_one(api_key: str, prompt: str, retries: int = 4) -> bytes:
    url = f"https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent?key={api_key}"
    body = {
        "contents": [{"parts": [{"text": prompt}]}],
        "generationConfig": {"responseModalities": ["TEXT", "IMAGE"]},
    }
    data = json.dumps(body).encode()
    last_err: Exception | None = None
    for attempt in range(retries):
        req = urllib.request.Request(url, data=data, headers={"Content-Type": "application/json"}, method="POST")
        try:
            with urllib.request.urlopen(req, timeout=120) as resp:
                payload = json.loads(resp.read().decode())
            for cand in payload.get("candidates", []):
                for part in cand.get("content", {}).get("parts", []):
                    inline = part.get("inlineData") or part.get("inline_data")
                    if inline and inline.get("data"):
                        return base64.b64decode(inline["data"])
            raise RuntimeError(f"No image in response: {json.dumps(payload)[:300]}")
        except Exception as e:  # noqa: BLE001
            last_err = e
            msg = str(e)
            # Rate limit needs longer cool-down
            if "429" in msg or "Too Many Requests" in msg:
                time.sleep(20 * (attempt + 1))
            else:
                time.sleep(2.5 * (attempt + 1))
    raise RuntimeError(str(last_err))


def process_row(api_key: str, row: dict) -> str:
    filename = row["filename"]
    raw_path = RAW_DIR / filename
    out_path = OUT_DIR / filename

    if SKIP_EXISTING and out_path.is_file() and out_path.stat().st_size > 2000:
        # still ensure navy/size if regenerating forced — skip when set
        return f"SKIP {filename}"

    png_bytes = generate_one(api_key, row["prompt"])
    RAW_DIR.mkdir(parents=True, exist_ok=True)
    raw_path.write_bytes(png_bytes)

    img = force_navy(Image.open(BytesIO(png_bytes)))
    img = crop_center(img)
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    img.save(out_path, "PNG", optimize=True)
    return f"OK {filename}"


def main() -> None:
    rows = json.loads(PROMPTS.read_text())
    if OFFSET:
        rows = rows[OFFSET:]
    if LIMIT > 0:
        rows = rows[:LIMIT]

    api_key = load_api_key()
    print(f"Generating {len(rows)} icons with {MODEL} workers={WORKERS}")
    ok = err = skip = 0
    errors: list[str] = []

    with ThreadPoolExecutor(max_workers=WORKERS) as pool:
        futs = {pool.submit(process_row, api_key, row): row for row in rows}
        for i, fut in enumerate(as_completed(futs), 1):
            row = futs[fut]
            try:
                msg = fut.result()
                if msg.startswith("SKIP"):
                    skip += 1
                else:
                    ok += 1
                print(f"[{i}/{len(rows)}] {msg}")
            except Exception as e:  # noqa: BLE001
                err += 1
                errors.append(f"{row['filename']}: {e}")
                print(f"[{i}/{len(rows)}] ERR {row['filename']}: {e}", file=sys.stderr)

    print(f"\nDone. OK={ok} SKIP={skip} ERR={err}")
    if errors:
        Path("/tmp/pk-fourcat-gen-errors.json").write_text(json.dumps(errors, indent=2))
        print("Errors written to /tmp/pk-fourcat-gen-errors.json")
        sys.exit(2)


if __name__ == "__main__":
    main()
