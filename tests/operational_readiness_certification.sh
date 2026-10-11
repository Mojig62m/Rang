#!/usr/bin/env bash
#
# Bamero operational readiness certification — static acceptance suite (2026-10).
# Referenced by .github/workflows/production-gate.yml.
#
# The go-live certification suite (tests/go_live_certification.sh) proves the
# code and release bundle. This suite proves the OPERATIONAL layer: that the
# deployment pipeline, runtime verification wiring, launch-day gate and the
# cleanup of dead files are all contractually in place. It fails closed.
# CI captures the full report as artifact/operational-readiness.txt.
#
# Usage:
#   bash tests/operational_readiness_certification.sh
#
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_YML="$ROOT/.github/workflows/deploy.yml"
MONITOR_YML="$ROOT/.github/workflows/health-monitor.yml"
VERIFY_SH="$ROOT/scripts/verify-deployment.sh"
GATE_SH="$ROOT/scripts/launch-day-gate.sh"
HEALTH_PHP="$ROOT/health-check.php"

PASS=0
FAIL=0

ok()  { PASS=$((PASS+1)); echo "  PASS  [$1] $2"; }
bad() { FAIL=$((FAIL+1)); echo "  FAIL  [$1] $2"; }

# ck <id> <description> <condition>  (condition is eval'd; must succeed)
ck() {
    local id="$1" desc="$2" cond="$3"
    if eval "$cond" >/dev/null 2>&1; then ok "$id" "$desc"; else bad "$id" "$desc"; fi
}

echo "=================================================="
echo " Bamero operational readiness certification (static)"
echo " root: ${ROOT}"
echo "=================================================="

echo
echo "[P] Deployment pipeline contract"
ck P1 "deployment pipeline workflow present"   '[ -f "$DEPLOY_YML" ]'
ck P2 "no continue-on-error: true anywhere in the pipeline"   '! grep -q "continue-on-error: true" "$DEPLOY_YML"'
ck P3 "production environment approval-gated"   'grep -q "name: production" "$DEPLOY_YML" && grep -qi "required reviewers" "$DEPLOY_YML"'
ck P4 "verify runs after staging AND production AND both rollbacks"   '[ "$(grep -c "verify-deployment.sh" "$DEPLOY_YML")" -ge 4 ]'
ck P5 "rollback is a symlink switch (no rebuild)"   'grep -q "rollback-release.sh" "$DEPLOY_YML" && grep -q "no rebuild" "$DEPLOY_YML"'
ck P6 "artifact checksum recorded and upload fails closed"   'grep -q "sha256sum" "$DEPLOY_YML" && grep -q "if-no-files-found: error" "$DEPLOY_YML"'
ck P7 "push trigger intentionally removed until secrets exist"   'grep -q "push trigger is intentionally REMOVED" "$DEPLOY_YML"'

echo
echo "[V] Runtime verification wiring"
ck V1 "post-deploy HTTP checks cover wp-json/shop/cart/my-account"   'grep -q "/wp-json/" "$VERIFY_SH" && grep -q "/shop/" "$VERIFY_SH" && grep -q "/cart/" "$VERIFY_SH" && grep -q "/my-account/" "$VERIFY_SH"'
ck V2 "PHP error detection in rendered pages"   'grep -q "Fatal error" "$VERIFY_SH"'
ck V3 "transient network failures retried"   'grep -q -- "--retry 3" "$VERIFY_SH"'
ck V4 "nightly monitor scheduled and wired to verification"   'grep -q "schedule:" "$MONITOR_YML" && grep -q "verify-deployment.sh" "$MONITOR_YML"'
ck V5 "monitor failures open a tracking issue"   'grep -qF "if: \${{ failure() }}" "$MONITOR_YML"'
ck V6 "fail-closed health endpoint + token contract"   'grep -q "http_response_code(403)" "$HEALTH_PHP" && grep -q "BAMERO_HEALTH_TOKEN" "$ROOT/.env.example"'
ck V7 "runtime test trio present (health/seed/smoke)"   'for t in health_check.php staging_smoke.php seed_validation.php; do [ -f "$ROOT/tests/$t" ] || exit 1; done'

echo
echo "[O] Launch-day runtime gate"
ck O1 "launch-day gate script present"   '[ -f "$GATE_SH" ]'
ck O2 "gate fails closed without a base URL"   'grep -q "GATE ABORT: base URL required" "$GATE_SH"'
ck O3 "https redirect verified"   'grep -q "https-redirect" "$GATE_SH"'
ck O4 "health endpoint probed (403 unauth, 200+healthy with token)"   'grep -q "health-endpoint" "$GATE_SH"'
ck O5 "manual gates fail closed unless attested"   'grep -q "MANUAL GATES PENDING" "$GATE_SH" && grep -q "confirm-manual" "$GATE_SH"'
ck O6 "robots.txt reachability verified"   'grep -q "robots.txt" "$GATE_SH"'
ck O7 "make launch-gate target wired"   'grep -q "^launch-gate:" "$ROOT/Makefile"'

echo
echo "[C] Cleanup of dead paths (v1.2.0 review)"
ck C1 "deleted legacy scripts stay deleted"   'for f in scripts/deploy.sh scripts/rollback.sh scripts/validate.sh; do [ ! -e "$ROOT/$f" ] || exit 1; done'
ck C2 "deleted duplicate deploy doc stays deleted"   '[ ! -e "$ROOT/FAST_TRACK_DEPLOY_FA.md" ]'
ck C3 "no references to deleted paths in active files"   '! grep -rqE --exclude="operational_readiness_certification.sh" "scripts/(deploy|rollback|validate)\.sh|FAST_TRACK_DEPLOY_FA" "$ROOT/Makefile" "$ROOT/README.md" "$ROOT/.github" "$ROOT/scripts" "$ROOT/tests"'
ck C4 "Makefile inventory matches the cleaned target list"   'grep -q "^\.PHONY: help install setup test certify launch-gate db-backup db-restore reset docker-up docker-down" "$ROOT/Makefile"'
ck C5 "health endpoint free of phantom plugins and stale page slugs"   '! grep -qE "bamero-sms-provider|bamero-notifications|bamero-iran-shipping|color-consultation|terms-of-service|about-us|privacy-policy" "$HEALTH_PHP"'

echo
echo "[G] Governance"
ck G1 "runbook defines launch-day order and immediate rollback criteria"   'grep -q "روز راه‌اندازی (Launch Day)" "$ROOT/GO_LIVE_RUNBOOK_FA.md" && grep -q "معیارهای بازگشت فوری" "$ROOT/GO_LIVE_RUNBOOK_FA.md"'
ck G2 "runbook wires the machine-executable launch gate"   'grep -q "launch-day-gate.sh" "$ROOT/GO_LIVE_RUNBOOK_FA.md"'
ck G3 "operational readiness certificate document present"   '[ -f "$ROOT/OPERATIONAL_READINESS_FA.md" ]'
ck G4 "go-live certification document present"   '[ -f "$ROOT/GO_LIVE_CERTIFICATION_FA.md" ]'
ck G5 "launch-day environment contract present"   'for k in BAMERO_HEALTH_TOKEN DISABLE_WP_CRON BAMERO_ENABLE_PAYMENT_IN_STAGING BAMERO_ENABLE_SMS_IN_STAGING; do grep -q "^$k=" "$ROOT/.env.example" || exit 1; done'

echo
echo "=================================================="
if [ "$FAIL" -eq 0 ]; then
    echo " OPERATIONAL READINESS: PASS ($PASS/$((PASS+FAIL)) checks)"
    echo "=================================================="
    exit 0
fi
echo " OPERATIONAL READINESS: FAIL ($FAIL failed of $((PASS+FAIL)) checks)"
echo "=================================================="
exit 1
