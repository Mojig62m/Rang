#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
if ! docker compose ps --status=running --services | grep -qx 'wordpress'; then
  echo 'WordPress is not running. Start it with: docker compose up -d db wordpress'
  exit 1
fi
docker compose --profile tools run --rm wpcli eval-file /var/www/html/wp-content/themes/bamero/tools/provision-bamero-catalog.php --user=1
printf 'Catalog provisioning completed. Verify with: docker compose --profile tools run --rm wpcli post list --post_type=product --post_status=publish\n'
