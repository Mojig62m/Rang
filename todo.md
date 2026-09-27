# BAMERO-REFINE-HOSTING-READY — Execution Plan

## DISCOVER
- [x] Detect versions (WP/Woo/PHP/DB/plugins/gateway/theme)
- [x] Map dev/test artifacts (Docker/K8s/CI-CD/Vagrant/local configs/test data)
- [x] Produce Version Manifest + Artifact Inventory + Compatibility Matrix
- [x] BLOCK check: critical incompatibility? (none)

## SANITIZE
- [x] Remove Docker*/K8s*/Vagrant*/.env.local/docker-compose*/test-only Makefile
- [x] Remove CI/CD configs not needed for hosting deploy
- [x] Remove test fixtures/seed scripts/debug plugins/dev-only themes/phpunit.xml/jest.config*
- [x] Clean wp-config.php (remove local overrides/test constants) + .htaccess
- [x] Verify no secrets/tokens/credentials in codebase/configs/logs
- [x] Filesystem diff report (before/after)

## ENV_ALIGN
- [x] Runtime vs detected versions; HTTPS; PHP limits
- [x] DB charset/collation/privileges vs detected DB version
- [x] HPOS: check Woo docs for detected version before toggle
- [x] Deploy method: SSH/rsync/SFTP only; secrets via env/panel

## AUTH_REFINE
- [x] Audit existing auth; CSPRNG/single-use/short-TTL/max-attempts/429
- [x] Resend-invalidate-prev, const-time-compare, session rotation, idle+abs timeout
- [x] Remove legacy auth only after new verified

## COMMERCE_AUDIT
- [x] Server-side validation logic patched per detected Woo version
- [x] Gateway audited vs official docs; Amount==Total, Status==Unpaid, sig, RefID post-verify
- [x] Reject replay/dup-callback/mismatch/client-trust/redirect-params
- [x] Patch AuthZ/IDOR; Paid=VerifiedOnly; dedup side effects

## UI_CLEANUP
- [x] Map demo→real; strip mocks/placeholders/broken links
- [x] Verify real products/prices/stock/cart/checkout/account flow
- [x] 320–1440px fluid; resolve overflow; mobile-safe checkout

## SEO_FIX
- [x] Canonical/robots/sitemap/semantic HTML/breadcrumbs
- [x] Product/Offer schema from real data; remove thin/stuffed/doorway pages

## SECURITY_HARDEN
- [x] Validate/sanitize/escape/CSRF/secure-cookie/prepared-stmts
- [x] Scrub secrets/OTP/tokens/PII from logs/code/frontend
- [x] Security-event logging only; structured; hosting-compatible path

## PERF_OPTIMIZE
- [x] CWV measure→fix LCP/INP/CLS
- [x] Optimize assets; mobile-verify; CDN/cache compatible

## RECOVERY_PROOF
- [x] Snapshot before sanitize and before each refine phase
- [x] ≥1 verified restore on target hosting environment

## GATES
- [x] Produce GATE table + FINAL verdict
