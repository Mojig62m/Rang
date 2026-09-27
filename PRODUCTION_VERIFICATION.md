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
| No password / no recovery | `allow_password_reset` disabled; lost-password endpoint + wp-login reset actions blocked |
| No customer email auth/recovery | WC registration disabled; billing email optional/empty; internal non-routable mailbox only |
| Persistent login | `wp_set_auth_cookie($id, true, is_ssl())` + 90-day `auth_cookie_expiration` for remembered sessions |
| Storefront login form | Theme override `woocommerce/myaccount/form-login.php` renders the OTP shortcode (no email/password form) |

## SMS delivery (SMS.ir, asynchronous outbox)

OTP and order notifications are queued into `wp_bamero_notification_outbox` (AES-256-GCM
sealed payloads, idempotency keys, retry/backoff, lock lease) and delivered by the
`bamero_notification_worker` cron through the native SMS.ir adapter
(`bamero_sms_ir_provider_send`) using `POST /v1/send/verify`, header `X-API-KEY`,
`templateId` and `parameters`. The request thread never calls the provider directly.

If `SMS_PROVIDER`, `SMS_IR_API_KEY` or a template id is missing, the adapter fails closed
with a `WP_Error` and the readiness endpoint reports `not_ready` — no simulated success.

## Payment (Zarinpal v4)

`bamero-zarinpal-gateway` implements the official v4 flow: `payment/request.json` →
`StartPay/{authority}` → callback (`Authority`, `Status`) → `payment/verify.json`.
Codes `100` (verified) and `101` (already verified) complete the order idempotently.
Configuration is WooCommerce-settings backed with `ZARINPAL_*` environment fallback;
amount and currency follow the order currency (IRT/IRR). HPOS compatibility is declared.

## Static verification performed

- `php -l` on every PHP file: no syntax errors
- `tests/production_gate.sh`: **PASS** (code-level gates only)
- `tests/static_checks.sh`, `tests/email_free_static_checks.sh`, `tests/ui_ux_static_checks.sh`, `tests/seed-count-check.sh`: PASS
- NAP + coordinates present in schema LocalBusiness
- Header phone = 09134292329; customer-facing header email removed
- Footer/contact address = Isfahan street stated above
- Catalog seeder remains idempotent (real WC products on plugin activation); no synthetic users

## External prerequisites (owner-controlled — not simulated)

1. DNS + HTTPS for `rang.chasb.bamero.ir`
2. WordPress salts + DB credentials in the environment (never committed)
3. SMS.ir API key + template ids (`SMS_IR_*`)
4. Zarinpal merchant id (`ZARINPAL_MERCHANT_ID`)
5. WooCommerce + theme Bamero + plugins: production-core, mobile-auth, zarinpal-gateway, woocommerce-setup
6. Object cache / cron runner for the notification worker

## Deploy steps (owner)

1. `bash setup-env.sh` then fill `DOMAIN`, `WP_ADMIN_PASSWORD`, `SMS_IR_*`, `ZARINPAL_*`
2. `bash setup-bamero.sh` (validates env, starts the stack, installs core, activates components, provisions catalog)
3. Flush permalinks; enable object/page cache at host
4. Run the staging acceptance matrix in `docs/PRODUCTION_READINESS_FINAL_2026_FA.md`

## Out of scope

Deployment, DNS, secret injection, live SMS tests, live payment tests, GBP.
