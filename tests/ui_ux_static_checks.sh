#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
fail=0
pass(){ printf 'PASS: %s\n' "$1"; }
failcheck(){ printf 'FAIL: %s\n' "$1"; fail=1; }
check(){ if "$@"; then pass "$*"; else failcheck "$*"; fi; }
check_not_rg(){ local label="$1"; shift; if rg -n "$@" >/dev/null 2>&1; then failcheck "$label"; else pass "$label"; fi; }

check test -s "$ROOT/wp-content/themes/bamero/assets/fonts/Vazirmatn-wght.woff2"
check test -s "$ROOT/wp-content/themes/bamero/assets/fonts/OFL.txt"
check test -s "$ROOT/wp-content/themes/bamero/css/icons.css"
check_not_rg 'no external font/icon CDN or CSS import' 'fonts\.googleapis|cdnjs\.cloudflare|@import' "$ROOT/wp-content/themes/bamero" --glob='*.php' --glob='*.css'
check_not_rg 'no physical float or spacing directions' 'float:\s*(left|right)|(?:margin|padding)-(?:left|right)\s*:' "$ROOT/wp-content/themes/bamero" --glob='*.css'
check_not_rg 'no physical text alignment directions' 'text-align:\s*(left|right)' "$ROOT/wp-content/themes/bamero" --glob='*.css'
check rg -n 'font-display:\s*swap' "$ROOT/wp-content/themes/bamero/css/variables.css"
check rg -n 'env\(safe-area-inset-bottom\)' "$ROOT/wp-content/themes/bamero/css/woocommerce.css"
check rg -n 'prefers-reduced-motion' "$ROOT/wp-content/themes/bamero" --glob='*.css'
check rg -n "'bamero-icons'|bamero_preload_primary_font" "$ROOT/wp-content/themes/bamero/functions.php"

used=$(rg -o --no-filename 'var\(--[A-Za-z0-9_-]+' "$ROOT/wp-content/themes/bamero" --glob='*.css' | sed 's/var(//' | sort -u)
defined=$(rg -o --no-filename -- '--[A-Za-z0-9_-]+:' "$ROOT/wp-content/themes/bamero/css/variables.css" | sed 's/:$//' | sort -u)
missing=$(comm -23 <(printf '%s\n' "$used") <(printf '%s\n' "$defined") || true)
if [ -z "$missing" ]; then pass 'all CSS custom properties have a token definition'; else printf 'MISSING CSS TOKENS:\n%s\n' "$missing"; fail=1; fi

exit "$fail"
