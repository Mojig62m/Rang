#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
mode="${1:-static}"
fail=0
pass(){ printf 'PASS: %s\n' "$1"; }
need(){ if [[ -n "${!1:-}" ]]; then pass "$1 configured"; else printf 'FAIL: %s missing\n' "$1"; fail=1; fi; }

command -v php >/dev/null 2>&1 && pass 'PHP CLI available' || { echo 'FAIL: PHP CLI missing'; fail=1; }
for file in \
  "$ROOT/wp-config.php" \
  "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php" \
  "$ROOT/wp-content/plugins/bamero-mobile-auth/bamero-mobile-auth.php" \
  "$ROOT/wp-content/plugins/bamero-zarinpal-gateway/bamero-zarinpal-gateway.php" \
  "$ROOT/wp-content/themes/bamero/theme.json"; do
  [[ -s "$file" ]] && pass "file ${file#$ROOT/}" || { echo "FAIL: file ${file#$ROOT/}"; fail=1; }
done

if [[ "$mode" == "runtime" ]]; then
  [[ "${WP_ENVIRONMENT_TYPE:-}" == "staging" || "${WP_ENVIRONMENT_TYPE:-}" == "production" ]] && pass 'WP_ENVIRONMENT_TYPE is staging/production' || { echo 'FAIL: WP_ENVIRONMENT_TYPE must be staging or production'; fail=1; }
  for key in DB_NAME DB_USER DB_PASSWORD DB_HOST AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT SMS_IR_API_KEY ZARINPAL_MERCHANT_ID; do need "$key"; done
  [[ "${SMS_PROVIDER:-}" == "sms_ir" ]] && pass 'SMS_PROVIDER=sms_ir' || { echo 'FAIL: SMS_PROVIDER must be sms_ir'; fail=1; }
  [[ "${ZARINPAL_API_BASE_URL:-}" == https://* ]] && pass 'ZARINPAL_API_BASE_URL uses HTTPS' || { echo 'FAIL: ZARINPAL_API_BASE_URL must use HTTPS'; fail=1; }
  [[ "${SMS_IR_API_BASE_URL:-}" == https://* ]] && pass 'SMS_IR_API_BASE_URL uses HTTPS' || { echo 'FAIL: SMS_IR_API_BASE_URL must use HTTPS'; fail=1; }
else
  pass 'static mode: external credentials intentionally not required'
  printf 'NOTE: runtime mode must pass in production-like staging before Go-Live.\n'
fi

exit "$fail"
