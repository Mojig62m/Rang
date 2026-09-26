#!/data/data/com.termux/files/usr/bin/bash
# source this file:  source .env.export.sh
set -a
# shellcheck disable=SC1091
. "$(dirname "${BASH_SOURCE[0]:-$0}")/.env"
set +a
echo "Env loaded. DOMAIN still empty? → edit .env"
