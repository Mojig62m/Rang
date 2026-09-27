#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
python3 - "$ROOT" <<'PY'
import pathlib, re, sys
root = pathlib.Path(sys.argv[1])
checks = [
    ("woocommerce-setup.php", "bamero_wc_setup_activate", "bamero_wc_setup_require_schema", "bamero_create_product_categories"),
    ("woocommerce-setup.php", "bamero_create_product_categories", "bamero_wc_setup_schema_is_validated", "wp_insert_term"),
    ("woocommerce-setup.php", "bamero_create_product_tags", "bamero_wc_setup_schema_is_validated", "wp_insert_term"),
    ("woocommerce-setup.php", "bamero_create_catalog_products", "bamero_wc_setup_schema_is_validated", "wp_insert_post"),
    ("woocommerce-setup.php", "bamero_configure_woocommerce", "bamero_wc_setup_schema_is_validated", "update_option"),
    ("bamero-mobile-auth.php", "bamero_request_otp", "bamero_auth_schema_validate", "set_transient"),
    ("bamero-mobile-auth.php", "bamero_verify_otp", "bamero_auth_schema_validate", "set_transient"),
    ("bamero-mobile-auth.php", "bamero_register_customer", "schema_invalid", "wp_insert_user"),
    ("bamero-mobile-auth.php", "bamero_handle_verify_otp", "bamero_auth_schema_validate", "$verified = bamero_verify_otp"),
]
paths = {
    "woocommerce-setup.php": root / "wp-content/plugins/bamero-woocommerce-setup/bamero-woocommerce-setup.php",
    "bamero-mobile-auth.php": root / "wp-content/plugins/bamero-mobile-auth/bamero-mobile-auth.php",
}
def body(text, name):
    match = re.search(r"function\s+" + re.escape(name) + r"\s*\([^)]*\)\s*\{", text)
    if not match: raise AssertionError(f"missing function {name}")
    start, depth, i = match.start(), 0, match.end() - 1
    while i < len(text):
        if text[i] == "{": depth += 1
        elif text[i] == "}":
            depth -= 1
            if depth == 0: return text[start:i+1]
        i += 1
    raise AssertionError(f"unterminated function {name}")
core = root / "wp-content/plugins/bamero-production-core/bamero-production-core.php"
core_text = core.read_text(encoding="utf-8")
core_body = body(core_text, "bamero_queue_notification")
if "bamero_notification_schema_validate" not in core_body or core_body.index("bamero_notification_schema_validate") > core_body.index("$wpdb->"):
    raise AssertionError("production-core:bamero_queue_notification: database operation precedes schema validation")
for filename, fn, validation, write in checks:
    b = body(paths[filename].read_text(encoding="utf-8"), fn)
    if validation not in b: raise AssertionError(f"{filename}:{fn}: schema guard missing")
    if write not in b: raise AssertionError(f"{filename}:{fn}: write token missing")
    if b.index(validation) > b.index(write): raise AssertionError(f"{filename}:{fn}: write precedes schema validation")
print(f"PASS: {len(checks) + 1} no-write-before-schema assertions; source-order checks only")
PY
