# Bamero (Rang) — Production Hardening & Go-Live Task Plan

## 0. Environment & Baseline
- [x] Install PHP 8.3 CLI for real syntax lint
- [x] Run full production gate to capture true baseline
- [x] Research official Zarinpal v4 + SMS.ir REST docs (authoritative)

## 1. Remove Dirty / Dead / Mock / Sample / Obsolete Code
- [x] Remove legacy `setup-bamero.sh` (placeholder config, fake email/phone, auto git push, random CDN font)
- [x] Clean `.env.example` (duplicate + unused generic SMS_* vars)
- [x] Update `setup-env.sh` to emit SMS.ir + Zarinpal env (remove dead `bamero_send_sms` reference)
- [x] Remove dead `bamero_send_sms()` + unused `bamero_otp_rate_key()`; fix OTP TTL comment
- [x] Remove `bamero_create_test_users()` sample users (fake emails) + update seed gate
- [x] Remove no-op `bamero_resource_hints`; fix footer placeholder email/phone
- [x] Fix readiness check wrong filter (`bamero_sms_provider` -> `bamero_sms_provider_send`)

## 2. Phone-only Auth Requirements
- [x] Registration = first name + last name + phone only (no password, no email)
- [x] Disable WooCommerce lost-password / password reset entirely
- [x] Ensure no customer email is ever required/sent
- [x] Persistent login (no repeated re-login) with long-lived cookie

## 3. Zarinpal Gateway (per official docs)
- [x] Harden request/verify/callback, amount unit, currency, idempotency, error handling
- [x] WC settings-backed config + env fallback

## 4. Verification
- [x] PHP lint all files
- [x] Run production gate + all static gates (green)
- [x] Produce final Go-Live readiness report with evidence

## 5. Delivery
- [x] Commit to branch + push + open PR
