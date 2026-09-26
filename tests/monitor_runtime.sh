#!/usr/bin/env bash
set -euo pipefail
BASE_URL="${1:?usage: monitor_runtime.sh https://staging.example.com}"
BASE_URL="${BASE_URL%/}"
CURL_ARGS=(--fail --silent --show-error --location --connect-timeout 5 --max-time 15 --proto '=https' --tlsv1.2)
check_json(){
  local name="$1" url="$2" expected="$3" body status
  body="$(curl "${CURL_ARGS[@]}" --write-out $'\n%{http_code}' "$url")"
  status="${body##*$'\n'}"
  body="${body%$'\n'*}"
  test "$status" = "$expected"
  printf '%s status=%s body=%s\n' "$name" "$status" "$body"
}
check_json liveness "$BASE_URL/wp-json/bamero/v1/health/live" 200
check_json readiness "$BASE_URL/wp-json/bamero/v1/health/ready" 200
printf 'RUNTIME MONITOR: PASS — live and ready endpoints passed against %s\n' "$BASE_URL"
