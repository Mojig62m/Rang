#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/wp-content/plugins/bamero-woocommerce-setup/bamero-woocommerce-setup.php"
product_count=$(rg -c "array\('name' =>" "$PLUGIN")
[ "$product_count" -eq 10 ] || { echo "FAIL: products=$product_count (expected 10)"; exit 1; }
if rg -n "bamero_create_test_users|example\.invalid|_bamero_test_segment" "$PLUGIN" >/dev/null; then
  echo "FAIL: synthetic/sample user seeding still present in setup plugin"; exit 1
fi
! rg -n "10000|10,000|500 users|2000 orders|2,000 orders" "$PLUGIN" >/dev/null
echo "PASS: exactly 10 catalog products; no synthetic/sample users; no bulk seed markers"
