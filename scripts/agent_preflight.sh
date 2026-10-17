#!/usr/bin/env bash
#
# Bamero agent preflight — executable version of copilot-agent-contract.md §4
# (Phase 1: environment verification) and AGENT_STARTUP_CHECKLIST.md.
#
# This is the machine-checkable gate an agent (or a human) MUST run before
# starting any issue. Fail-closed: any failed check exits non-zero and no
# work should begin.
#
# Usage:
#   bash scripts/agent_preflight.sh              # full preflight (gate included)
#   bash scripts/agent_preflight.sh --skip-gate  # skip production_gate.sh (no PHP CLI)
#   make preflight
#
# Exits: 0 = GO (all checks pass), 1 = STOP (fail-closed).
#
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SKIP_GATE=0
[ "${1:-}" = "--skip-gate" ] && SKIP_GATE=1

FAIL=0
ok()  { echo "  PASS  [$1] $2"; }
bad() { FAIL=1; echo "  FAIL  [$1] $2"; }

echo "=================================================="
echo " Bamero agent preflight (contract §4 / Phase 1)"
echo " root: ${ROOT}"
echo "=================================================="

# ---- [1] contract presence (source of truth) ------------------------------
if [ -f "$ROOT/.github/copilot-agent-contract.md" ]; then
    ok "contract" ".github/copilot-agent-contract.md present"
    if grep -q "معیار وضوح" "$ROOT/.github/copilot-agent-contract.md" 2>/dev/null; then
        ok "contract" "clarity-criteria section present"
    else
        bad "contract" "clarity-criteria section missing — verify file integrity"
    fi
elif [ -f "$ROOT/copilot-agent-contract.md" ]; then
    ok "contract" "root contract present (consider syncing a copy to .github/)"
else
    bad "contract" "copilot-agent-contract.md not found — STOP"
fi

# ---- [2] agent docs present ------------------------------------------------
for f in README_FOR_AGENT.md AGENT_OPERATIONAL_DIRECTIVE.md AGENT_QUICK_START.md AGENT_STARTUP_CHECKLIST.md; do
    if [ -f "$ROOT/$f" ]; then ok "agent-docs" "$f"; else bad "agent-docs" "$f missing"; fi
done

# ---- [3] main freshness (only meaningful inside a git clone) --------------
if git -C "$ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    if git -C "$ROOT" fetch origin main >/dev/null 2>&1; then
        LOCAL=$(git -C "$ROOT" rev-parse main 2>/dev/null || echo unknown)
        REMOTE=$(git -C "$ROOT" rev-parse origin/main 2>/dev/null || echo unknown)
        if [ "$LOCAL" = "$REMOTE" ]; then
            ok "main-fresh" "main matches origin/main (${LOCAL:0:7})"
        else
            bad "main-fresh" "main ($LOCAL) diverges from origin/main ($REMOTE) — pull first"
        fi
    else
        echo "  WARN  [main-fresh] fetch failed (offline?) — verify freshness manually"
    fi
    LAST_MSG=$(git -C "$ROOT" log -1 --format=%s 2>/dev/null || echo "")
    if echo "$LAST_MSG" | grep -qiE "wip|draft|todo"; then
        bad "main-clean" "last commit message looks unfinished: $LAST_MSG"
    else
        ok "main-clean" "last commit message: $LAST_MSG"
    fi
else
    echo "  WARN  [main-fresh] not a git worktree — skipping git checks"
fi

# ---- [4] blocking PRs (dynamic rule, contract §3.1) -----------------------
# Contract v1.1: any OPEN PR overlapping the issue's topic/files blocks work.
# Without gh or network this check degrades to a WARN, never a silent PASS.
if command -v gh >/dev/null 2>&1 && gh auth status >/dev/null 2>&1; then
    OPEN_PRS=$(gh pr list --repo Mojig62m/Rang --state open --json number,title --jq '.[] | "#\(.number) \(.title)"' 2>/dev/null || echo "")
    if [ -z "$OPEN_PRS" ]; then
        ok "blocking-prs" "no open PRs — full access"
    else
        echo "  WARN  [blocking-prs] open PRs found — check overlap with your issue:"
        echo "$OPEN_PRS" | sed 's/^/         /'
        echo "         rule: overlapping topic/files => STOP; independent => continue with Draft PR"
    fi
else
    echo "  WARN  [blocking-prs] gh CLI unavailable — check https://github.com/Mojig62m/Rang/pulls manually"
fi

# ---- [5] production gate (the actual quality gate) ------------------------
if [ "$SKIP_GATE" -eq 1 ]; then
    echo "  SKIP  [gate] production_gate.sh skipped by --skip-gate (run it before any PR)"
elif [ ! -f "$ROOT/tests/production_gate.sh" ]; then
    bad "gate" "tests/production_gate.sh missing"
else
    if command -v php >/dev/null 2>&1; then
        if bash "$ROOT/tests/production_gate.sh"; then
            ok "gate" "production_gate.sh PASS"
        else
            bad "gate" "production_gate.sh FAIL — fixing the gate is the top priority"
        fi
    else
        echo "  WARN  [gate] PHP CLI unavailable — cannot run production_gate.sh locally"
        echo "         CI (.github/workflows/production-gate.yml) is the authoritative runner"
    fi
fi

# ---- result ---------------------------------------------------------------
echo "=================================================="
if [ "$FAIL" -eq 0 ]; then
    echo " PREFLIGHT: GO"
    echo "=================================================="
    exit 0
fi
echo " PREFLIGHT: STOP — fix the failures above before starting any issue"
echo "=================================================="
exit 1
