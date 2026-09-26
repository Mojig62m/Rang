#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
check(){ if "$@"; then printf 'PASS: %s\n' "$*"; else printf 'FAIL: %s\n' "$*"; exit 1; fi; }
check test -s "$ROOT/.github/workflows/production-gate.yml"
check test -s "$ROOT/docker-compose.production.yml"
check rg -n 'MARIADB_IMAGE:\?Set immutable|WORDPRESS_IMAGE:\?Set immutable' "$ROOT/docker-compose.production.yml"
not_rg(){ ! rg -n "$1" "${@:2}"; }
check not_rg 'rootlocal|WP_DEBUG_LOG=1|unsafe-eval' "$ROOT/docker-compose.yml" "$ROOT/docker-compose.production.yml" "$ROOT/.env.example" "$ROOT/setup-env.sh" "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php"
check rg -n "script-src \\'self\\' \\'nonce-|style-src-attr" "$ROOT/wp-content/plugins/bamero-production-core/bamero-production-core.php"
printf 'PASS: supply-chain production preflight; registry/image scan remains deployment-time\n'
