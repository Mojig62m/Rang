#!/usr/bin/env bash
# Reproducible release gate. Runtime/provider checks are intentionally separate.
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
fail=0
pass() { printf 'PASS: %s\n' "$1"; }
fail() { printf 'FAIL: %s\n' "$1" >&2; fail=1; }
require_file() { [[ -s "$ROOT/$1" ]] && pass "file $1" || fail "missing/empty file $1"; }

command -v php >/dev/null 2>&1 || { printf 'RELEASE GATE: FAIL — PHP CLI is required for syntax validation.\n' >&2; exit 2; }
command -v sha256sum >/dev/null 2>&1 || { printf 'RELEASE GATE: FAIL — sha256sum is required for evidence.\n' >&2; exit 2; }

for f in wp-config.php .env.example README.md \
  wp-content/themes/bamero/style.css \
  wp-content/themes/bamero/theme.json \
  wp-content/themes/bamero/functions.php \
  wp-content/plugins/bamero-production-core/bamero-production-core.php \
  tests/quantitative_readiness.py; do require_file "$f"; done

while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null && pass "PHP lint ${file#$ROOT/}" || fail "PHP lint ${file#$ROOT/}"
done < <(find "$ROOT" -type f -name '*.php' -not -path '*/docs/*' -print0)

php -r 'json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);' "$ROOT/wp-content/themes/bamero/theme.json" && pass 'theme.json valid JSON' || fail 'theme.json invalid JSON'

if grep -RInE '(^|[^A-Za-z])(sk|pk)_[A-Za-z0-9]{12,}|-----BEGIN (RSA|OPENSSH|EC) PRIVATE KEY-----|AKIA[0-9A-Z]{16}' "$ROOT" --exclude-dir=.git --exclude='*.log' --exclude='*.pdf' >/dev/null; then
  fail 'possible secret detected'
else
  pass 'no common committed secret pattern detected'
fi

bash "$ROOT/tests/static_checks.sh" >/dev/null && pass 'static security checks' || fail 'static security checks'
bash "$ROOT/tests/ui_ux_static_checks.sh" >/dev/null && pass 'static UI/UX checks' || fail 'static UI/UX checks'
bash "$ROOT/tests/email_free_static_checks.sh" >/dev/null && pass 'email-free boundary checks' || fail 'email-free boundary checks'
bash "$ROOT/tests/seed-count-check.sh" >/dev/null && pass 'seed boundary checks' || fail 'seed boundary checks'

if [[ "${CI:-0}" == "1" ]]; then
  manifest="$ROOT/docs/verification-evidence/sha256-manifest-ci.txt"
  verify_log="$ROOT/docs/verification-evidence/sha256-verify-2026-09-21.log"
  gate_log="$ROOT/docs/verification-evidence/production-gate-2026-09-21.log"
  find "$ROOT" -type f -not -path '*/.git/*' -not -path "$manifest" -not -path "$verify_log" -not -path "$gate_log" -print0 | sort -z | xargs -0 sha256sum > "$manifest"
  pass 'SHA-256 evidence manifest generated'
fi

if (( fail )); then
  printf 'RELEASE GATE: FAIL — runtime, staging and external-provider gates remain separate.\n' >&2
  exit 1
fi
printf 'RELEASE GATE: PASS — code-level gates only; see docs/PLAYBOOK_COMPLIANCE_AUDIT_2026_FA.md for runtime gates.\n'
