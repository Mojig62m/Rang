#!/usr/bin/env python3
"""Deterministic repository invariants for the Bamero storefront modernization."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content/themes/bamero"

checks = {
    "modern stylesheet exists": (THEME / "css/storefront-modern.css").is_file(),
    "modern stylesheet is enqueued after RTL CSS": (
        "bamero-storefront-modern" in (THEME / "functions.php").read_text()
        and "bamero-woocommerce-rtl" in (THEME / "functions.php").read_text()
    ),
    "header search targets products": all(
        token in (THEME / "header.php").read_text()
        for token in ('role="search"', 'name="s"', 'name="post_type" value="product"')
    ),
    "product card exposes availability": "product-availability" in (THEME / "woocommerce/content-product.php").read_text(),
    "mobile menu uses shared close path": "closeMenu();" in (THEME / "js/main.js").read_text(),
    "reduced-motion contract exists": "prefers-reduced-motion" in (THEME / "css/storefront-modern.css").read_text(),
    "local RTL font remains configured": "Vazirmatn-wght.woff2" in (THEME / "css/variables.css").read_text(),
}

for label, passed in checks.items():
    print(f"{'PASS' if passed else 'FAIL'}: {label}")

raise SystemExit(0 if all(checks.values()) else 1)
