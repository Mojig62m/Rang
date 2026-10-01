# BAMERO-GO-LIVE-PHP-HOSTING — Execution Plan

> Goal: Clean the project and convert it to a go-live-ready state for **PHP shared hosting**.
> Zarinpal API, SMS.ir API, domain and DB credentials stay as **placeholders / env vars** — the owner fills them in at the end.

## AUDIT
- [x] Inventory repo (126 files), read core docs, plugins, theme, configs
- [x] Identify stale references, hardcoded values, hosting blockers
- [x] Confirm remaining issues list (7 items)

## CLEAN (repo hygiene)
- [x] Fix stale repo URLs + author in plugin headers
- [x] Fix stale repo URLs / structure diagram in README
- [x] Remove hardcoded production domain (robots.txt → dynamic + docs)
- [x] Normalize plugin folder structure (loose file + empty folder)
- [x] Make phone/address consistently configurable (theme_mod + Customizer)

## HOSTING-COMPAT (PHP shared hosting)
- [x] Add `.env` file loader to wp-config.php (works without panel env vars)
- [x] Add optional WP_HOME / WP_SITEURL domain override via env
- [x] Update .env.example (domain + hosting notes)
- [x] Ensure .env is protected (htaccess) and document outside-webroot option

## DOCS
- [x] Rewrite README install/deploy section for PHP hosting
- [x] Write Persian PHP-hosting go-live guide (GO_LIVE_PHP_HOSTING_FA.md)
- [x] Add theme README (referenced but missing)

## PACKAGE
- [x] Build clean deploy package (full site + wp-content overlay)
- [x] Verify package integrity + list contents

## VERIFY
- [x] Run production gate (php lint + duplicate funcs + secret scan + CSP) → PASS
- [x] Verify .env loader behavior (precedence, quotes, spaces)
- [x] Final consistency scan
