#!/usr/bin/env python3
"""Extract the Master OS explorer into admin public assets."""

from __future__ import annotations

import re
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SRC_HTML = ROOT / "system-modules.html"
DEST = ROOT / "panun-admin" / "public" / "assets" / "admin-module" / "operating-system"
ART_SRC = ROOT / "os-art"
ART_DEST = DEST / "art"

DATA_FILES = [
    "system-modules-data.js",
    "system-os-docs.js",
    "system-os-story.js",
]


def extract_style(html: str) -> str:
    match = re.search(r"<style>(.*?)</style>", html, re.S)
    if not match:
        raise SystemExit("No <style> block found in system-modules.html")
    return match.group(1).strip() + "\n"


def extract_app_js(html: str) -> str:
    match = re.search(
        r"<script>\s*(const DATA = window\.PK_SYSTEM;.*)</script>\s*</body>",
        html,
        re.S,
    )
    if not match:
        raise SystemExit("No OS app script found in system-modules.html")
    return match.group(1).strip() + "\n"


def split_selectors(selector: str) -> list[str]:
    parts: list[str] = []
    buf: list[str] = []
    depth = 0
    for char in selector:
        if char == "(":
            depth += 1
        elif char == ")":
            depth = max(0, depth - 1)
        if char == "," and depth == 0:
            parts.append("".join(buf).strip())
            buf = []
            continue
        buf.append(char)
    tail = "".join(buf).strip()
    if tail:
        parts.append(tail)
    return parts


def prefix_one(selector: str) -> str:
    selector = selector.strip()
    if not selector:
        return selector
    if selector.startswith("@") or selector.startswith(".os-explorer"):
        return selector
    if selector in {"from", "to"} or re.match(r"^\d+(\.\d+)?%$", selector):
        return selector
    if selector in {":root", "html", "body"}:
        return ".os-explorer"
    if selector.startswith("html"):
        return ".os-explorer" + selector[4:]
    if selector.startswith("body"):
        return ".os-explorer" + selector[4:]
    if selector == "*":
        return ".os-explorer *"
    return ".os-explorer " + selector


def prefix_selector_list(selector: str) -> str:
    return ", ".join(prefix_one(part) for part in split_selectors(selector))


def prefix_css(css: str) -> str:
    out: list[str] = []
    i = 0
    n = len(css)

    def copy_block(start: int) -> tuple[str, int]:
        depth = 0
        j = start
        while j < n:
            if css[j] == "{":
                depth += 1
            elif css[j] == "}":
                depth -= 1
                if depth == 0:
                    return css[start : j + 1], j + 1
            j += 1
        raise SystemExit("Unbalanced CSS braces")

    while i < n:
        if css.startswith("/*", i):
            end = css.find("*/", i + 2)
            if end < 0:
                out.append(css[i:])
                break
            out.append(css[i : end + 2])
            i = end + 2
            continue

        if css[i].isspace():
            out.append(css[i])
            i += 1
            continue

        if css.startswith("@keyframes", i) or css.startswith("@-webkit-keyframes", i):
            brace = css.find("{", i)
            out.append(css[i:brace])
            block, i = copy_block(brace)
            out.append(block)
            continue

        if css.startswith("@media", i) or css.startswith("@supports", i):
            brace = css.find("{", i)
            out.append(css[i : brace + 1])
            inner_start = brace + 1
            depth = 1
            j = inner_start
            while j < n:
                if css[j] == "{":
                    depth += 1
                elif css[j] == "}":
                    depth -= 1
                    if depth == 0:
                        break
                j += 1
            out.append(prefix_css(css[inner_start:j]))
            out.append("}")
            i = j + 1
            continue

        brace = css.find("{", i)
        if brace < 0:
            out.append(css[i:])
            break
        selector = css[i:brace]
        block, i = copy_block(brace)
        out.append(prefix_selector_list(selector))
        out.append(block)

    return "".join(out)


SHELL_CSS = """
.os-operating-system-page {
  flex: 1 1 auto;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 0 !important;
  max-width: none !important;
  background: #f4f0e7;
}
html:has(.os-operating-system-page),
html:has(.os-operating-system-page) body {
  height: 100%;
  overflow: hidden;
}
body.nav-top .main-area:has(.os-operating-system-page),
.main-area:has(.os-operating-system-page) {
  overflow: hidden;
  block-size: 100vh !important;
  block-size: 100dvh !important;
  max-block-size: 100dvh !important;
  min-block-size: 0 !important;
}
.main-area:has(.os-operating-system-page) > .main-content,
.main-area:has(.os-operating-system-page) .admin-main-frame {
  flex: 1 1 auto;
  min-height: 0;
  height: auto;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.main-area:has(.os-operating-system-page) > footer.footer,
.main-area:has(.os-operating-system-page) .admin-main-frame > footer.footer {
  display: none !important;
}
.os-explorer {
  flex: 1 1 auto;
  min-height: 0;
  height: 100%;
  overflow: hidden;
  position: relative;
  font-family: Figtree, system-ui, sans-serif;
  color: #16162a;
  line-height: 1.45;
  -webkit-font-smoothing: antialiased;
}
.os-explorer *,
.os-explorer *::before,
.os-explorer *::after {
  box-sizing: border-box;
}
.os-explorer button,
.os-explorer input {
  font: inherit;
  color: inherit;
}
.os-explorer button {
  background: none;
  border: 0;
  cursor: pointer;
  box-shadow: none;
  border-radius: 0;
  padding: 0;
  line-height: inherit;
}
.os-explorer a {
  color: inherit;
  text-decoration: none;
}
.os-explorer img {
  max-width: none;
  vertical-align: middle;
}
.os-explorer ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.os-explorer p,
.os-explorer h1,
.os-explorer h2,
.os-explorer h3,
.os-explorer h4 {
  margin: 0;
}
.os-explorer table {
  caption-side: top;
}
.os-explorer .topbar,
.os-explorer .topbar h1,
.os-explorer .topbar .brand,
.os-explorer .topbar .menu-btn,
.os-explorer .topbar .mso {
  color: #fff;
}
.os-explorer .topbar .brand h1 span {
  color: rgba(255, 255, 255, 0.5);
}
.os-explorer .main,
.os-explorer .sidebar {
  color: #16162a;
}
.os-explorer .main h1,
.os-explorer .main h2,
.os-explorer .main h3,
.os-explorer .main h4,
.os-explorer .sidebar h1,
.os-explorer .sidebar h2,
.os-explorer .sidebar h3 {
  color: inherit;
}
.os-explorer h2.serif {
  color: #16162a;
}
"""


def rewrite_js(js: str) -> str:
    js = js.replace(
        'const V = "os30";\n    const art = (file) => "os-art/" + file + "?v=" + V;',
        'const V = root.getAttribute("data-version") || "os30";\n    const art = (file) => (root.getAttribute("data-art-base") || "").replace(/\\/$/, "") + "/" + file + "?v=" + V;',
    )
    js = js.replace("    const $ = (id) => document.getElementById(id);\n", "")
    replacements = {
        '$("sidebar")': 'byId("os-sidebar")',
        '$("scrim")': 'byId("os-scrim")',
        '$("results")': 'byId("os-results")',
        '$("q")': 'byId("os-q")',
        '$("main")': 'byId("os-main")',
        '$("progressChip")': 'byId("os-progress-chip")',
        '$("menuBtn")': 'byId("os-menu-btn")',
    }
    for old, new in replacements.items():
        js = js.replace(old, new)
    if "$(" in js:
        raise SystemExit("Unrewritten $() helper remains in OS app JS")

    slash = '''    document.addEventListener("keydown", (e) => {
      if (e.key === "/" && document.activeElement !== byId("os-q")) {
        e.preventDefault();
        byId("os-q").focus();
        byId("os-q").select();
      }
    });'''
    slash_new = '''    document.addEventListener("keydown", (e) => {
      if (e.key !== "/") return;
      const typing = e.target && e.target.closest && e.target.closest("input, textarea, select, [contenteditable='true']");
      if (typing) return;
      e.preventDefault();
      const q = byId("os-q");
      if (!q) return;
      q.focus();
      q.select();
    });'''
    js = js.replace(slash, slash_new)

    return (
        "(function () {\n"
        '  "use strict";\n'
        '  const root = document.getElementById("os-explorer");\n'
        "  if (!root || !window.PK_SYSTEM || !window.PK_OS) return;\n"
        "  const byId = (id) => root.querySelector(\"#\" + id);\n\n"
        + js
        + "\n})();\n"
    )


def main() -> None:
    html = SRC_HTML.read_text(encoding="utf-8")
    DEST.mkdir(parents=True, exist_ok=True)
    ART_DEST.mkdir(parents=True, exist_ok=True)

    css = prefix_css(extract_style(html)) + "\n" + SHELL_CSS
    (DEST / "os.css").write_text(css, encoding="utf-8")
    (DEST / "os-app.js").write_text(rewrite_js(extract_app_js(html)), encoding="utf-8")

    for name in DATA_FILES:
        shutil.copy2(ROOT / name, DEST / name)

    for png in ART_SRC.glob("*.png"):
        shutil.copy2(png, ART_DEST / png.name)

    print(f"Wrote CSS/JS and {len(list(ART_DEST.glob('*.png')))} art files to {DEST}")


if __name__ == "__main__":
    main()
