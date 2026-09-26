#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
fail=0
check(){ if "$@"; then printf 'PASS: %s\n' "$*"; else printf 'FAIL: %s\n' "$*"; fail=1; fi; }
not_rg(){ ! rg -n "$1" "$2" ${3:-}; }
check test -f "$ROOT/wp-content/themes/bamero/woocommerce/archive-product.php"
check test -f "$ROOT/wp-content/themes/bamero/woocommerce/single-product.php"
check test -f "$ROOT/wp-content/themes/bamero/css/components-production.css"
check test -f "$ROOT/wp-content/plugins/bamero-zarinpal-gateway/bamero-zarinpal-gateway.php"
check test ! -f "$ROOT/wp-content/themes/bamero/shop.php"
check test ! -f "$ROOT/wp-content/themes/bamero/woocommerce.php"
check not_rg '\$wpdb|SELECT |INSERT |UPDATE |DELETE ' "$ROOT/wp-content/themes/bamero" '--glob=*.php'
check rg -n '!\$product instanceof WC_Product|wc_get_product' "$ROOT/wp-content/themes/bamero/woocommerce/single-product.php"
check rg -n 'BAMERO_CART_SELECTOR|bamero-mini-cart' "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php" "$ROOT/wp-content/themes/bamero/header.php" "$ROOT/wp-content/themes/bamero/js/main.js"
check rg -n 'readme\\.html' "$ROOT/.htaccess"
check rg -n 'hash_hmac|hash_equals|BAMERO_PAYMENT_WEBHOOK_SECRET' "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php"
check rg -n 'SMS_IR_API_KEY|/send/verify|X-API-KEY' "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php"
check rg -n 'payment/request\.json|payment/verify\.json|payment_complete|Authority' "$ROOT/wp-content/plugins/bamero-zarinpal-gateway/bamero-zarinpal-gateway.php"
check rg -n 'X-Request-ID|DONOTCACHEPAGE|declare_compatibility' "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php"
check rg -n 'payload_ciphertext|payload_expires_at|aes-256-gcm|UNIQUE KEY idem' "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php"
check rg -n 'bamero_notification_schema_validate|Notification schema validation failed' "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php"
check rg -n '@container|container-type' "$ROOT/wp-content/themes/bamero/css/components-production.css"
check bash "$ROOT/tests/no_write_before_schema.sh"
check bash "$ROOT/tests/monitoring_checks.sh"
check test -x "$ROOT/tests/monitor_runtime.sh"
check bash "$ROOT/tests/supply_chain_preflight.sh"
check rg -n 'DISALLOW_FILE_MODS' "$ROOT/wp-config.php"
check not_rg '\b(margin|padding|inset)-(left|right)\s*:' "$ROOT/wp-content/themes/bamero" '--glob=*.css'
check not_rg 'put-your-unique-phrase' "$ROOT/wp-config.php"
printf 'NOTE: PHP 8.3 lint is executed separately; WordPress integration tests still require staging.\n'
exit "$fail"
