#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CORE="$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php"
check(){ if "$@"; then printf 'PASS: %s\n' "$*"; else printf 'FAIL: %s\n' "$*"; exit 1; fi; }
check rg -n "register_rest_route\('bamero/v1', '/health/live'" "$CORE"
check rg -n "register_rest_route\('bamero/v1', '/health/ready'" "$CORE"
check rg -n "permission_callback.*__return_true" "$CORE"
check rg -n "WP_REST_Response.*503|\$ready \? 200 : 503" "$CORE"
check rg -n "database.*bamero_health_db_probe|outbox_table|sms_provider_configured|zarinpal_secret" "$CORE"
check rg -n "rest_api_init.*bamero_health_rest_routes|add_action\('rest_api_init'" "$CORE"
if rg -n "update_option\('bamero_production_core_health'" "$CORE"; then
  echo 'FAIL: production core performs health option write at load time'; exit 1
fi
printf 'PASS: monitoring static checks; no runtime claim made\n'
