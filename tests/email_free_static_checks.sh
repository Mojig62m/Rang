#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
fail=0
pass(){ printf 'PASS: %s\n' "$1"; }
failcheck(){ printf 'FAIL: %s\n' "$1"; fail=1; }
if rg -n --glob='*.php' '(wp_mail|PHPMailer|SMTP|mail\(|wp_new_user_notification_email)' "$ROOT" >/tmp/bamero_email_matches 2>&1; then
  cat /tmp/bamero_email_matches; failcheck 'no forbidden email transport symbols in executable PHP';
else pass 'no forbidden email transport symbols in executable PHP'; fi
for f in "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php" "$ROOT/wp-content/plugins/bamero-mobile-auth/bamero-mobile-auth.php"; do
  if rg -n 'bamero_queue_notification|bamero_notification_outbox|wp_hash_password|wp_check_password' "$f" >/dev/null; then pass "security/outbox controls present: $f"; else failcheck "security/outbox controls missing: $f"; fi
done
if rg -n 'woocommerce_email_enabled_|pre_wp_' "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php" >/dev/null; then pass 'platform notification blocking hooks present'; else failcheck 'platform notification blocking hooks missing'; fi
exit "$fail"
