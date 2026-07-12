#!/usr/bin/env python3
"""Deprecated — use prepare_laundry_assets.py + generate_laundry_variant_icons.py (carpentry repair format)."""

from __future__ import annotations

import runpy
import sys
from pathlib import Path

print("Run instead:", file=sys.stderr)
print("  python3 scripts/assets/prepare_laundry_assets.py", file=sys.stderr)
print("  python3 scripts/assets/variant-icons/generate_laundry_variant_icons.py", file=sys.stderr)
sys.exit(1)
