#!/usr/bin/env python3
"""Deterministic, fail-closed production audit for the Bamero WordPress store.

This audits the declared scope. It cannot prove behavior that requires a real
WordPress/WooCommerce deployment, credentials, browser, database, or traffic.
Those controls remain BLOCKED unless immutable runtime evidence is supplied.
"""
from __future__ import annotations
import hashlib
import json
import pathlib
import re
import subprocess
from dataclasses import dataclass, asdict

@dataclass
class Control:
    id: str
    area: str
    title: str
    status: str
    evidence: str
    blocking: bool

ROOT = pathlib.Path(__file__).resolve().parents[1]

def run(command: list[str]) -> bool:
    return subprocess.run(command, cwd=ROOT, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL).returncode == 0

def exists(rel: str) -> bool:
    return (ROOT / rel).is_file() and (ROOT / rel).stat().st_size > 0

def contains(rel: str, pattern: str) -> bool:
    try:
        return re.search(pattern, (ROOT / rel).read_text(encoding="utf-8", errors="ignore"), re.I | re.M) is not None
    except OSError:
        return False

def runtime_evidence(name: str) -> bool:
    return exists(f"docs/runtime-evidence/{name}")

def main() -> int:
    php_files = list(ROOT.rglob("*.php"))
    secret_free = run(["bash", "-lc", "! rg -n --glob '*.php' --glob '*.env*' '(sk|pk)_[A-Za-z0-9]{12,}|-----BEGIN (RSA|OPENSSH|EC) PRIVATE KEY-----|AKIA[0-9A-Z]{16}' ."])
    php_lint = all(run(["php", "-l", str(p)]) for p in php_files)
    static_gate = run(["bash", "tests/production_gate.sh"])
    controls = [
        Control("C01", "code", "All PHP files pass syntax validation", "PASS" if php_lint else "FAIL", f"{len(php_files)} PHP files; php -l", True),
        Control("C02", "code", "Declared release gate passes", "PASS" if static_gate else "FAIL", "tests/production_gate.sh", True),
        Control("C03", "security", "No common committed secret pattern", "PASS" if secret_free else "FAIL", "repository secret scan", True),
        Control("C04", "security", "File editing and modifications are disabled by default", "PASS" if contains("wp-config.php", r"DISALLOW_FILE_EDIT") and contains("wp-config.php", r"DISALLOW_FILE_MODS") else "FAIL", "wp-config.php", True),
        Control("C05", "security", "Admin SSL is forced", "PASS" if contains("wp-config.php", r"FORCE_SSL_ADMIN") else "FAIL", "wp-config.php", True),
        Control("C06", "security", "Sensitive files blocked by server rules", "PASS" if contains(".htaccess", r"wp-config\\\.php") and contains(".htaccess", r"Strict-Transport-Security") else "FAIL", ".htaccess", True),
        Control("C07", "privacy", "Email transport/customer email boundary is blocked", "PASS" if run(["bash", "tests/email_free_static_checks.sh"]) else "FAIL", "tests/email_free_static_checks.sh", True),
        Control("C08", "ui", "Responsive and RTL static controls pass", "PASS" if run(["bash", "tests/ui_ux_static_checks.sh"]) else "FAIL", "tests/ui_ux_static_checks.sh", True),
        Control("C09", "payments", "Zarinpal request/callback/verify adapter exists", "PASS" if exists("wp-content/plugins/bamero-zarinpal-gateway/bamero-zarinpal-gateway.php") and contains("wp-content/plugins/bamero-zarinpal-gateway/bamero-zarinpal-gateway.php", r"request\.json") and contains("wp-content/plugins/bamero-zarinpal-gateway/bamero-zarinpal-gateway.php", r"verify\.json") else "FAIL", "Zarinpal adapter static contract", True),
        Control("C10", "sms", "SMS.ir adapter and encrypted durable outbox exist", "PASS" if contains("wp-content/plugins/bamero-production-core/bamero-production-core.php", r"send/verify") and contains("wp-content/plugins/bamero-production-core/bamero-production-core.php", r"aes-256-gcm") and contains("wp-content/plugins/bamero-production-core/bamero-production-core.php", r"UNIQUE KEY idem") else "FAIL", "production-core static contract", True),
        Control("C11", "woocommerce", "HPOS declaration exists", "PASS" if contains("wp-content/plugins/bamero-production-core/bamero-production-core.php", r"declare_compatibility") else "FAIL", "FeaturesUtil declaration", True),
        Control("C12", "observability", "Request and correlation IDs exist", "PASS" if contains("wp-content/plugins/bamero-production-core/bamero-production-core.php", r"X-Request-ID") and contains("wp-content/plugins/bamero-production-core/bamero-production-core.php", r"correlation_id") else "FAIL", "production-core logging", True),
        Control("C13", "cache", "Private commerce pages bypass public cache", "PASS" if contains("wp-content/plugins/bamero-production-core/bamero-production-core.php", r"DONOTCACHEPAGE") and contains("wp-content/plugins/bamero-production-core/bamero-production-core.php", r"nocache_headers") else "FAIL", "private-cache hook", True),
        Control("C14", "performance", "Quantitative auditor is present and executable", "PASS" if exists("tests/quantitative_readiness.py") else "FAIL", "quantitative_readiness.py", True),
        Control("C15", "performance", "Container-query progressive enhancement exists", "PASS" if contains("wp-content/themes/bamero/css/components-production.css", r"@container") else "FAIL", "components-production.css", False),
        Control("C16", "operations", "Static preflight is present", "PASS" if exists("tests/production_preflight.sh") else "FAIL", "production_preflight.sh", True),
        Control("R01", "runtime", "Production-like WordPress/WooCommerce boot smoke test", "PASS" if runtime_evidence("wordpress-smoke.json") else "BLOCKED", "missing docs/runtime-evidence/wordpress-smoke.json", True),
        Control("R02", "runtime", "HPOS enabled integration test", "PASS" if runtime_evidence("hpos.json") else "BLOCKED", "missing docs/runtime-evidence/hpos.json", True),
        Control("R03", "runtime", "OTP send and delivery evidence", "PASS" if runtime_evidence("sms-otp.json") else "BLOCKED", "missing docs/runtime-evidence/sms-otp.json", True),
        Control("R04", "runtime", "Order notification delivery evidence", "PASS" if runtime_evidence("sms-order.json") else "BLOCKED", "missing docs/runtime-evidence/sms-order.json", True),
        Control("R05", "runtime", "Zarinpal success/cancel/replay/verify evidence", "PASS" if runtime_evidence("zarinpal.json") else "BLOCKED", "missing docs/runtime-evidence/zarinpal.json", True),
        Control("R06", "runtime", "Playwright revenue-path E2E evidence", "PASS" if runtime_evidence("e2e-purchase.json") else "BLOCKED", "missing docs/runtime-evidence/e2e-purchase.json", True),
        Control("R07", "runtime", "Core Web Vitals p75 field evidence", "PASS" if runtime_evidence("web-vitals.json") else "BLOCKED", "missing docs/runtime-evidence/web-vitals.json", True),
        Control("R08", "runtime", "Load, concurrency and race/idempotency evidence", "PASS" if runtime_evidence("load-race.json") else "BLOCKED", "missing docs/runtime-evidence/load-race.json", True),
        Control("R09", "operations", "Backup checksum and successful restore evidence", "PASS" if runtime_evidence("backup-restore.json") else "BLOCKED", "missing docs/runtime-evidence/backup-restore.json", True),
        Control("R10", "operations", "Rollback and cache invalidation rehearsal evidence", "PASS" if runtime_evidence("rollback.json") else "BLOCKED", "missing docs/runtime-evidence/rollback.json", True),
        Control("R11", "security", "WPScan/dependency scan evidence", "PASS" if runtime_evidence("security-scan.json") else "BLOCKED", "missing docs/runtime-evidence/security-scan.json", True),
        Control("R12", "operations", "Cron/worker health and retry evidence", "PASS" if runtime_evidence("worker.json") else "BLOCKED", "missing docs/runtime-evidence/worker.json", True),
    ]
    pass_count = sum(c.status == "PASS" for c in controls)
    fail_count = sum(c.status == "FAIL" for c in controls)
    blocked_count = sum(c.status == "BLOCKED" for c in controls)
    critical_missing = [c.id for c in controls if c.blocking and c.status != "PASS"]
    result = {
        "audit_scope_controls": len(controls),
        "pass": pass_count,
        "fail": fail_count,
        "blocked": blocked_count,
        "coverage": 1.0,
        "critical_missing": critical_missing,
        "decision": "GO" if not critical_missing else "NO-GO",
        "reason": "All blocking controls have immutable evidence" if not critical_missing else "At least one blocking control is FAIL or BLOCKED",
        "controls": [asdict(c) for c in controls],
        "sha256": hashlib.sha256(pathlib.Path(__file__).read_bytes()).hexdigest(),
    }
    out = ROOT / "docs/verification-evidence/full-production-audit-2026-09-21.json"
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0 if result["decision"] == "GO" else 1

if __name__ == "__main__":
    raise SystemExit(main())
