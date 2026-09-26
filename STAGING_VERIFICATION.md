# Staging verification

## Environment definition

`docker-compose.yml` defines MariaDB 10.11, WordPress 6.7 on PHP 8.3/Apache, WP-CLI, persistent database/core/uploads volumes, and the repository `wp-content` mount. `staging/README.md` documents the installer and teardown commands.

## Execution status (2026-09-14)

The environment definition was added, but execution is **blocked** in this runner: `docker`, `docker compose`, and a local MySQL/MariaDB server are unavailable. WordPress core is not vendored, so no browser-accessible site, database, plugin activation, seed routine, or end-to-end browser evidence could honestly be produced.

| Verification | Result | Evidence/limitation |
| --- | --- | --- |
| Compose configuration | Not executed | Docker CLI unavailable. |
| WordPress/WooCommerce installation | Blocked | Requires Docker/network and database runtime. |
| Desktop/tablet/mobile/RTL browser inspection | Blocked | No running site or browser harness. |
| Admin, forms, checkout, product metadata | Blocked | Requires WordPress + WooCommerce database. |
| Payment/email | Not run | No authorized sandbox credentials; never simulated as live success. |

Once Docker is available, run the documented stack, install WooCommerce, activate `bamero`, `bamero-custom-plugin`, and `bamero-woocommerce-setup`, then capture Playwright/axe screenshots and update this file with versions, URLs, console/network results, and test artifacts.
