# Test matrix

| Check | Exact command/method | Scope | Result / exit | Evidence and limitations |
| --- | --- | --- | --- | --- |
| Baseline PHP syntax | `find wp-content -type f -name '*.php' -print0 \| xargs -0 -n1 php -l` | All PHP before changes | Pass / 0 | Recorded in `CODEX_BASELINE.md`. |
| Final PHP syntax | `find wp-content -type f -name '*.php' -print0 \| xargs -0 -n1 php -l` | All theme/plugin PHP | Pass / 0 | Every tracked PHP file parsed under PHP 8.5.7-dev. Local static check. |
| JavaScript syntax | `node --check wp-content/themes/bamero/js/main.js` | Theme JS | Pass / 0 | Local parser check; no browser behavior asserted. |
| Shell syntax | `bash -n setup-bamero.sh` | Setup script | Pass / 0 | Script not executed because it writes config and performs network/git operations. |
| Global declaration collision scan | Python `Path.rglob` function-name scan (see final command log) | Theme/plugins | Pass / 0 | No duplicate non-email global function declarations. Static check. |
| Security regression assertions | `rg` assertions for safe redirects, nonce/capability checks, hex sanitization, and removal of mutable archive | Changed security boundaries | Pass / 0 | Static source evidence only; WordPress request lifecycle unavailable. |
| WordPress/WooCommerce integration | WP-CLI/PHPUnit/browser | Hooks, templates, checkout, filters, activation | Blocked / not run | WordPress core, WooCommerce, database, and test config are not present. |
| Accessibility/RTL/responsive/fonts | Browser + axe/manual viewport method | Rendered frontend | Blocked / not run | No Playwright/Cypress/browser setup, local images/font, or running site is present. |
| Payment/email/live providers | Sandbox/live contract | Zarinpal, SMTP/provider | Blocked / not run | No credentials or external service authorization; never represented as successful. |

The final result is locally syntax-verified and statically hardened, with runtime and external-provider checks explicitly blocked by repository/environment limitations.

| Compose staging | `docker compose config` / `docker compose up -d` | WordPress + MariaDB + WP-CLI | Blocked / Docker unavailable | Definition committed in `docker-compose.yml`; execution details in `STAGING_VERIFICATION.md`. |
