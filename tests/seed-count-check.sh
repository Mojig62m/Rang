#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/wp-content/plugins/bamero-woocommerce-setup/bamero-woocommerce-setup.php"
product_count=$(rg -c "array\('name' =>" "$PLUGIN")
user_count=$(rg -o "'browser'|'buyer'|'abandoner'" "$PLUGIN" | wc -l | tr -d ' ')
[ "$product_count" -eq 10 ] || { echo "FAIL: products=$product_count (expected 10)"; exit 1; }
[ "$user_count" -eq 10 ] || { echo "FAIL: users=$user_count (expected 10)"; exit 1; }
! rg -n "10000|10,000|500 users|2000 orders|2,000 orders" "$PLUGIN" >/dev/null
echo "PASS: exactly 10 synthetic products and 10 synthetic users; no bulk seed markers"
