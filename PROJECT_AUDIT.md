# Project audit

## Architecture and feature inventory

This repository contains a classic PHP WordPress theme (`wp-content/themes/bamero`) and three standalone plugins: custom store functionality, WooCommerce demo provisioning, and an essential-plugin manager. The theme is Persian/RTL-oriented and provides classic page templates, WooCommerce template overrides, product metadata, product filters, a consultation form, and frontend assets. WooCommerce, Contact Form 7, payment gateways, WordPress core, and the promised font/images are not vendored.

## Findings and implementation status

| Area | Finding | Status |
| --- | --- | --- |
| Core WooCommerce routing | `woocommerce.php` incorrectly contained hook declarations instead of a template body. When selected as WooCommerce's catch-all template, it also redeclared functions already loaded by `functions.php`, causing a fatal error. | Fixed in this change. |
| Plugin coexistence | The custom plugin redeclared theme functions for the shortcode and product metadata whenever both were active. | Fixed in this change by retaining only page provisioning in the plugin. |
| Consultation request integrity | Form submission accepted invalid emails, did not require the advertised phone field, ignored mail failure, and used an untrusted referrer for redirect. | Fixed in this change. Live mail delivery remains unverified. |
| Product metadata | Color code was treated as arbitrary CSS, permitting invalid markup/CSS values. | Fixed in this change with canonical hex validation. |
| WooCommerce provisioner | It sideloaded each demo image twice, uses placeholder external URLs, and dereferences missing pages when applying WooCommerce settings. | Fixed in this change. External image retrieval remains unavailable. |
| Essential plugin manager | Its activation automatically installed and activated a remote dependency list, including a mutable GitHub `master` archive, which is an unsafe externally consequential action. | Fixed in this change: it is now administrator-initiated, nonce-protected, and installs WordPress.org plugins only. The Zarinpal gateway is documented as manual configuration. |
| Contact/newsletter | Contact page uses a Contact Form 7 shortcode if present, otherwise has an inert fallback; newsletter form has no handler. | Remains an integration gap; no requirements, provider, consent/retention policy, or test runtime exist. |
| Assets/fonts | References point to missing local images and a third-party font CDN. | Requires browser/network verification and asset decisions. |
| Accessibility/RTL | Semantic landmarks and a skip link exist, but no browser/a11y test infrastructure exists. Some hard-coded links/placeholders remain. | Partially inspectable only; requires rendered validation. |

## Security, performance, and deployment

No SQL, REST endpoints, AJAX handlers, cron, WP-CLI commands, migrations, or custom tables are present. The existing essential-plugin auto-installer and mutable external source were the highest-risk supply-chain behavior. Checkout, payment, WordPress roles, asset loading, HTTP headers, caching, and mail require a real WordPress/WooCommerce environment to verify.

## Pre-existing validation

PHP and shell syntax passed before changes; no project test/lint/build configuration was present. See `CODEX_BASELINE.md` and `TEST_MATRIX.md`.

## Staging environment

A reproducible Docker Compose definition is now present in `docker-compose.yml`; execution is blocked in the current runner because Docker and a local database daemon are unavailable. See `STAGING_VERIFICATION.md`.
