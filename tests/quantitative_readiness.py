#!/usr/bin/env python3
"""Evidence-driven quantitative release calculations.

No synthetic observations are generated. Runtime evidence is read from an optional
CSV with columns: gate, total, failures, metric, threshold.
"""
from __future__ import annotations
import argparse
import csv
import json
import math
import pathlib
import statistics
import subprocess
from dataclasses import dataclass, asdict

@dataclass
class GateResult:
    gate: str
    total: int
    failures: int
    success_rate: float
    error_rate: float
    wilson_lower_95: float
    status: str


def wilson_lower(successes: int, total: int, z: float = 1.959963984540054) -> float:
    if total <= 0:
        return float("nan")
    p = successes / total
    denominator = 1 + z * z / total
    centre = p + z * z / (2 * total)
    spread = z * math.sqrt((p * (1 - p) / total) + z * z / (4 * total * total))
    return (centre - spread) / denominator


def zero_failure_sample_size(target_reliability: float, confidence: float) -> int:
    """n so that (target reliability failure complement) is rejected at alpha."""
    if not (0 < target_reliability < 1 and 0 < confidence < 1):
        raise ValueError("targets must be between 0 and 1")
    return math.ceil(math.log(1 - confidence) / math.log(target_reliability))


def quantile(values: list[float], q: float) -> float:
    if not values:
        return float("nan")
    ordered = sorted(values)
    # nearest-rank, deliberately explicit for audit reproducibility
    rank = max(1, math.ceil(q * len(ordered)))
    return ordered[rank - 1]


def run_static_gate(root: pathlib.Path) -> tuple[bool, str]:
    proc = subprocess.run(["bash", str(root / "tests/production_gate.sh")], cwd=root,
                          text=True, capture_output=True)
    return proc.returncode == 0, proc.stdout + proc.stderr


def read_evidence(path: pathlib.Path) -> list[GateResult]:
    results: list[GateResult] = []
    with path.open(newline="", encoding="utf-8") as handle:
        for row in csv.DictReader(handle):
            gate = row["gate"].strip()
            total = int(row["total"])
            failures = int(row["failures"])
            if total <= 0 or failures < 0 or failures > total:
                raise ValueError(f"invalid count for {gate}")
            successes = total - failures
            rate = successes / total
            lower = wilson_lower(successes, total)
            results.append(GateResult(gate, total, failures, rate, failures / total,
                                      lower, "PASS" if failures == 0 else "FAIL"))
    return results


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", default=".")
    parser.add_argument("--evidence-csv", default=None)
    parser.add_argument("--out", default="docs/verification-evidence/quantitative-readiness.json")
    args = parser.parse_args()
    root = pathlib.Path(args.root).resolve()
    overall_confidence = 0.95
    target = 0.999
    critical_gate_count = 7
    bonferroni_alpha = (1 - overall_confidence) / critical_gate_count
    per_gate_confidence = 1 - bonferroni_alpha
    required_zero_failures = zero_failure_sample_size(target, per_gate_confidence)
    standard_zero_failures = zero_failure_sample_size(target, overall_confidence)
    static_pass, static_output = run_static_gate(root)
    php_files = sorted(root.glob("**/*.php"))
    css_files = sorted(root.glob("**/*.css"))
    static_checks = {
        "production_gate_exit_zero": static_pass,
        "php_file_count": len(php_files),
        "css_file_count": len(css_files),
    }
    evidence_results = []
    evidence_note = "No runtime evidence CSV supplied; runtime SLOs are not claimed."
    if args.evidence_csv:
        evidence_results = [asdict(r) for r in read_evidence(pathlib.Path(args.evidence_csv))]
        evidence_note = "Runtime evidence was supplied by the caller; inspect rows and assumptions."
    output = {
        "method": "binomial/Wilson lower bound, Bonferroni family-wise confidence, nearest-rank p75",
        "assumptions": {
            "target_reliability": target,
            "overall_confidence": overall_confidence,
            "critical_gate_count": critical_gate_count,
            "independence": "not assumed for approval; correlated failures require conservative interpretation",
            "runtime_data": evidence_note,
        },
        "equations": {
            "sli": "good_events / total_events",
            "error_budget": "1 - SLO",
            "zero_failure_n": "ceil(log(1-confidence) / log(target_reliability))",
            "wilson_lower": "(p+z^2/(2n)-z*sqrt(p(1-p)/n+z^2/(4n^2)))/(1+z^2/n)",
            "cwv": "nearest_rank(values, 0.75)",
        },
        "calculated_requirements": {
            "zero_failure_trials_at_95pct_for_99_9pct_reliability": standard_zero_failures,
            "zero_failure_trials_per_gate_with_95pct_family_confidence_across_7_gates": required_zero_failures,
            "availability_error_budget_per_1m_events_at_99_9pct": 1000,
        },
        "static_evidence": static_checks,
        "runtime_evidence": evidence_results,
    }
    output_path = root / args.out
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(output, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(output, ensure_ascii=False, indent=2))
    return 0 if static_pass else 1

if __name__ == "__main__":
    raise SystemExit(main())
