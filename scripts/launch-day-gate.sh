#!/usr/bin/env bash
#
# Bamero launch-day runtime gate — the executable part of the operational
# readiness certificate (OPERATIONAL_READINESS_FA.md).
#
# This gate converts the launch-day checklist (GO_LIVE_RUNBOOK_FA.md
# section 9) into machine-executable HTTP checks against the LIVE site.
# It fails closed: without a base URL, without the health token, and
# without explicit attestation of the four manual gates (M1-M4) it exits
# non-zero. No static inspection can substitute for it.
#
# Usage:
#   bash scripts/launch-day-gate.sh https://example.com --health-token "$BAMERO_HEALTH_TOKEN"
#   make launch-gate BASE_URL=https://example.com
#
#   Add --confirm-manual ONLY after M1-M4 have genuinely been executed
#   and their evidence recorded (GO_LIVE_RUNBOOK_FA.md section 9).
#
set -uo pipefail

BASE_URL=""
HEALTH_TOKEN=""
CONFIRM_MANUAL=0

while [ $# -gt 0 ]; do
    case "$1" in
        --health-token)
            if [ $# -lt 2 ]; then
                echo "GATE ABORT: --health-token requires a value" >&2
                exit 1
            fi
            HEALTH_TOKEN="$2"
            shift 2
            ;;
        --confirm-manual)
            CONFIRM_MANUAL=1
            shift
            ;;
        -h|--help)
            echo "Usage: bash scripts/launch-day-gate.sh https://DOMAIN [--health-token TOKEN] [--confirm-manual]"
            exit 0
            ;;
        --*)
            echo "GATE ABORT: unknown option $1" >&2
            exit 1
            ;;
        *)
            if [ -n "$BASE_URL" ]; then
                echo "GATE ABORT: base URL already set to $BASE_URL" >&2
                exit 1
            fi
            BASE_URL="$1"
            shift
            ;;
    esac
done

if [ -z "$BASE_URL" ]; then
    echo "GATE ABORT: base URL required (e.g. https://bamero.ir)" >&2
    exit 1
fi

case "$BASE_URL" in
    https://*) ;;
    *)
        echo "GATE ABORT: base URL must start with https:// — launching over plain HTTP is forbidden" >&2
        exit 1
        ;;
esac

PLAIN_URL="${BASE_URL#https://}"

PASS=0
FAIL=0

ok()  { PASS=$((PASS+1)); echo "  PASS  [$1] $2"; }
bad() { FAIL=$((FAIL+1)); echo "  FAIL  [$1] $2"; }

echo "=================================================="
echo " Bamero launch-day runtime gate"
echo " target: $BASE_URL"
echo "=================================================="

# ---- [1] https-redirect --------------------------------------------------
code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "http://$PLAIN_URL/" || echo 000)
case "$code" in
    301|308) ok "https-redirect" "http:// redirects with $code" ;;
    *)       bad "https-redirect" "expected 301/308 from http://$PLAIN_URL/, got $code" ;;
esac

# ---- [2] page checks -----------------------------------------------------
page_check() { # id path [marker]
    local id="$1" path="$2" marker="${3:-}"
    local target="$BASE_URL$path" code
    code=$(curl -sSL --retry 2 --retry-delay 2 --max-time 30 -o /tmp/gate_body -w '%{http_code}' "$target" || echo 000)
    if [ "$code" != "200" ]; then
        bad "$id" "HTTP $code for $target"
        return
    fi
    if grep -qiE "(Fatal error|Warning:|Parse error|Deprecated:)" /tmp/gate_body; then
        bad "$id" "PHP errors visible in $target"
        return
    fi
    if [ -n "$marker" ] && ! grep -q "$marker" /tmp/gate_body; then
        bad "$id" "marker '$marker' missing in $target"
        return
    fi
    ok "$id" "$target"
}

page_check "homepage"    "/"
page_check "wp-json"     "/wp-json/"
page_check "shop"        "/shop/"      "bmr"
page_check "cart"        "/cart/"
page_check "checkout"    "/checkout/"
page_check "my-account" "/my-account/"
page_check "robots-txt" "/robots.txt" "User-agent"

# ---- [3] health-endpoint (fail-closed contract) --------------------------
code=$(curl -s -o /tmp/gate_health -w '%{http_code}' --max-time 20 "$BASE_URL/health-check.php" || echo 000)
if [ "$code" = "403" ]; then
    ok "health-endpoint-locked" "unauthenticated health check correctly refused (403)"
else
    bad "health-endpoint-locked" "unauthenticated health check must answer 403, got $code"
fi

if [ -z "$HEALTH_TOKEN" ]; then
    bad "health-endpoint" "BAMERO_HEALTH_TOKEN missing — pass --health-token (fail-closed)"
else
    code=$(curl -s -o /tmp/gate_health -w '%{http_code}' --max-time 30 "$BASE_URL/health-check.php?token=$HEALTH_TOKEN" || echo 000)
    if [ "$code" = "200" ] && grep -q "healthy" /tmp/gate_health; then
        ok "health-endpoint" "authenticated health check reports healthy (200)"
    else
        bad "health-endpoint" "authenticated health check must answer 200 + healthy, got $code"
        sed -n '1,20p' /tmp/gate_health >&2 || true
    fi
fi

# ---- [4] manual gates M1-M4 (attestation, fail-closed) -------------------
echo
echo "MANUAL GATES (defined in GO_LIVE_RUNBOOK_FA.md section 9):"
echo "  M1 payment: real low-amount Zarinpal order ends paid + refund recorded"
echo "  M2 sms:     real OTP login + order notification SMS received"
echo "  M3 e2e:     product -> cart -> checkout -> paid order on production"
echo "  M4 vitals:  PageSpeed mobile LCP < 2.5s on home + one product page"
if [ "$CONFIRM_MANUAL" -eq 1 ]; then
    ok "manual-gates" "M1-M4 attested by the operator via --confirm-manual"
else
    bad "manual-gates" "MANUAL GATES PENDING: re-run with --confirm-manual only after executing M1-M4"
fi

# ---- result --------------------------------------------------------------
echo "=================================================="
if [ "$FAIL" -eq 0 ]; then
    echo " LAUNCH-DAY GATE: PASS (runtime)"
    echo "=================================================="
    exit 0
fi
echo " LAUNCH-DAY GATE: FAIL ($FAIL failed)"
echo "=================================================="
exit 1
