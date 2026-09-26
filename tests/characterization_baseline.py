#!/usr/bin/env python3
"""Phase 1 source characterization for the Bamero WordPress storefront."""
from __future__ import annotations

import json
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content/themes/bamero"


def run(command: list[str]) -> dict[str, object]:
    result = subprocess.run(command, cwd=ROOT, text=True, capture_output=True)
    return {"command": " ".join(command), "exit_code": result.returncode, "stdout": result.stdout[-4000:], "stderr": result.stderr[-4000:]}


def function_metrics(path: Path) -> list[dict[str, object]]:
    lines = path.read_text(encoding="utf-8", errors="replace").splitlines()
    metrics: list[dict[str, object]] = []
    pattern = re.compile(r"function\s+([A-Za-z0-9_]+)\s*\(")
    for index, line in enumerate(lines):
        match = pattern.search(line)
        if not match:
            continue
        depth = line.count("{") - line.count("}")
        end = index
        while end + 1 < len(lines) and depth > 0:
            end += 1
            depth += lines[end].count("{") - lines[end].count("}")
        body = lines[index : end + 1]
        complexity_tokens = sum(len(re.findall(r"\b(if|elseif|for|foreach|while|case|catch)\b|&&|\|\|", item)) for item in body)
        metrics.append({
            "name": match.group(1),
            "line": index + 1,
            "loc": len(body),
            "decision_token_count": complexity_tokens,
            "status": "flag-for-review" if len(body) > 30 and complexity_tokens > 8 else "not-flagged-by-screen",
        })
    return metrics


def main() -> int:
    php_files = sorted(ROOT.glob("**/*.php"))
    css_files = sorted(ROOT.glob("**/*.css"))
    js_files = sorted(ROOT.glob("**/*.js"))
    php_metrics = []
    for path in php_files:
        php_metrics.extend({"file": str(path.relative_to(ROOT)), **metric} for metric in function_metrics(path))
    data = {
        "phase": "1-characterization",
        "scope": "WordPress/WooCommerce theme, plugins, and repository checks",
        "files": {"php": len(php_files), "css": len(css_files), "javascript": len(js_files)},
        "lines": {
            "php": sum(len(path.read_text(encoding="utf-8", errors="replace").splitlines()) for path in php_files),
            "css": sum(len(path.read_text(encoding="utf-8", errors="replace").splitlines()) for path in css_files),
            "javascript": sum(len(path.read_text(encoding="utf-8", errors="replace").splitlines()) for path in js_files),
        },
        "responsive_scan": {
            "media_query_occurrences": sum(path.read_text(encoding="utf-8", errors="replace").count("@media") for path in css_files),
            "container_query_occurrences": sum(path.read_text(encoding="utf-8", errors="replace").count("@container") for path in css_files),
            "absolute_or_fixed_rules": sum(len(re.findall(r"position:\s*(absolute|fixed)", path.read_text(encoding="utf-8", errors="replace"))) for path in css_files),
            "important_occurrences": sum(path.read_text(encoding="utf-8", errors="replace").count("!important") for path in css_files),
        },
        "function_metrics": php_metrics,
        "executable_baseline": [
            run(["bash", "tests/production_gate.sh"]),
            run(["bash", "tests/ui_ux_static_checks.sh"]),
            run(["python3", "tests/storefront_modern_invariants.py"]),
            run(["node", "--check", "wp-content/themes/bamero/js/main.js"]),
        ],
        "interpretation": {
            "property_tests": "Not generated in Phase 1; existing deterministic invariants are recorded as baseline only.",
            "deletion_status": "No deletion or rewrite performed.",
            "complexity_status": "Decision-token counts are screening metrics, not McCabe or SonarQube proofs.",
            "runtime_status": "WordPress, WooCommerce, browser, database, payment, and provider behavior remain UNTESTABLE LEGACY without staging.",
        },
    }
    output = ROOT / "docs/verification-evidence/characterization-baseline-2026-09-21.json"
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(data, ensure_ascii=False, indent=2))
    return 0 if all(item["exit_code"] == 0 for item in data["executable_baseline"]) else 1


if __name__ == "__main__":
    raise SystemExit(main())
