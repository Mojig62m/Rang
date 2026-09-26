# Implementation plan

| ID | Problem and root cause | Affected components | Solution | Risk | Order | Test strategy / acceptance evidence |
| --- | --- | --- | --- | --- | --- | --- |
| SEC-01 | Plugin activation automatically performed remote installs and used a mutable external archive. | Essential plugin manager | Make installation an explicit, capability- and nonce-protected admin action; permit only WordPress.org sources; document manual gateway setup. | High | 1 | PHP lint; source assertions that activation does not install and handler checks nonce/capability. |
| CORE-01 | WooCommerce catch-all template contains declarations, not page output, and collides with `functions.php`. | Theme `woocommerce.php` | Replace with the standard header/content/footer template. | High | 2 | PHP lint and static duplicate-symbol scan. |
| CORE-02 | Custom plugin and theme declare identical global functions. | Custom plugin/theme | Retain page provisioning in plugin and single theme implementation for UI behavior. | High | 3 | Static duplicate-symbol scan and PHP lint. |
| SEC-02 | Consultation endpoint validation/error/redirect handling is unsafe or misleading. | Theme functions | Validate fields, use safe redirect, expose status safely, and send plain-text email only when valid. | High | 4 | PHP lint and static source checks. |
| DATA-01 | Demo product image duplicate request and unsafe absent page dereference. | WooCommerce setup plugin | Call sideload once, check errors, and only update existing page IDs. | Medium | 5 | PHP lint and source assertions. |
| DOC-01 | No truthful baseline, audit, test inventory, or risks exist. | Root documentation | Add evidence-based operational docs and update them after verification. | Medium | 6 | Review docs and final command log. |

All locally actionable tasks are implemented in this change. Remaining runtime/provider work is tracked in `RISK_REGISTER.md`.

| STAGE-01 | No executable staging definition existed. | Root Compose/staging docs | Added MariaDB, WordPress, WP-CLI, persistent volumes, and mounted project content. | Medium | 7 | Execute `docker compose up -d` when Docker is available; currently blocked and documented. |
