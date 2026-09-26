# Production readiness verification — Bamero

## Business facts applied (authoritative)

| Field | Value |
|-------|--------|
| Name | رنگ و چسب بامرو |
| Address | اصفهان، خیابان خرم، نرسیده به خیابان صارمیه |
| Phone | 09134292329 |
| Geo | 32.666756, 51.644733 |
| URL | https://rang.chasb.bamero.ir |
| Country | IR / Isfahan |

Google Business Profile: **not** created/claimed/modified (out of scope).

## Customer identity model

| Rule | Implementation |
|------|----------------|
| Identity = verified mobile | `bamero-mobile-auth` plugin, meta `bamero_verified_mobile` |
| Register = mobile + OTP + first + last name | `bamero_register_customer()` |
| Auth = mobile + OTP | No password login path for customers |
| No customer email auth/recovery | WC registration disabled; billing email optional/empty |
| Session | Standard WP auth cookies (`wp_set_auth_cookie`) |

SMS delivery is wired via filter `bamero_send_sms`. **Without an owner-provided SMS gateway, OTP send returns a clear WP_Error** — not simulated success.

## Static verification performed

- `php -l` on `functions.php` and `bamero-mobile-auth.php`: no syntax errors
- NAP + coordinates present in schema LocalBusiness
- Header phone = 09134292329; customer-facing header email removed
- Footer/contact address = Isfahan street stated above
- Catalog seeder remains idempotent (real WC products on plugin activation)

## External prerequisites (owner-controlled — not simulated)

1. DNS + HTTPS for `rang.chasb.bamero.ir`
2. WordPress salts in `wp-config.php` (replace placeholders)
3. Database credentials
4. SMS provider credentials + `add_filter('bamero_send_sms', ...)`
5. WooCommerce + theme Bamero + plugins: mobile-auth, woocommerce-setup
6. SMTP only if admin-side mail needed (not customer transactional identity)
7. Payment gateway (e.g. Zarinpal) configuration

## Deploy steps (owner)

1. Upload codebase
2. Configure `wp-config` DB + salts
3. Install WordPress core if needed; set site URL to https://rang.chasb.bamero.ir
4. Activate WooCommerce → Bamero theme → Bamero WooCommerce Setup → Bamero Mobile Auth
5. Wire SMS filter
6. Add `[bamero_mobile_auth]` to My Account or use `/mobile-login/`
7. Flush permalinks; enable object/page cache at host

## Out of scope

Deployment, DNS, secret injection, live SMS tests, live payment tests, GBP.
