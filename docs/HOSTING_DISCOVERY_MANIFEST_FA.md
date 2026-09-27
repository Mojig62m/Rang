# BAMERO-REFINE-HOSTING-READY — فاز DISCOVER (مانیفست نسخه‌ها و موجودی)

**تاریخ:** 2026-09-27 · **مخزن:** `Mojig62m/Rang` · **شاخهٔ پایه:** `hardening/production-ready-sms-zarinpal`
**قاعده:** DETECT_FIRST | NO_VERSION_ASSUME | NO_REBUILD | PRESERVE_DATA

---

## ۱. مانیفست نسخه‌ها (Version Manifest) — کشف‌شده از منبع

| مؤلفه | نسخهٔ هدف/کشف‌شده | منبع کشف | نوع |
|---|---|---|---|
| WordPress core | **6.7** (هدف استقرار) | `docker-compose.yml` → `wordpress:6.7-php8.3-apache` | هدف (core در مخزن نیست) |
| PHP | **8.3** (هدف) / 8.2.33 (CLI محلی) | workflow `php-version: 8.3`؛ `php -v` | هدف/محیط |
| WooCommerce | **9.x** (وابستگی) | هدر افزونه‌ها `Requires Plugins: woocommerce`؛ بدون pin نسخه | وابستگی |
| MariaDB | **10.11** | `docker-compose.yml` → `mariadb:10.11` | هدف |
| MySQL charset | `utf8mb4` / `utf8mb4_unicode_ci` | `docker-compose.yml`, `wp-config.php` | پیکربندی |
| Gateway | **Zarinpal v4** (افزونهٔ سفارشی 1.1.0) | `bamero-zarinpal-gateway.php` | سفارشی |
| SMS provider | **SMS.ir** REST (`/v1/send/verify`) | `bamero-production-core.php` | سفارشی |
| Theme | **Bamero 2.0.0** (Requires PHP 8.0, WP 6.0) | `themes/bamero/style.css` | سفارشی |
| Plugin: production-core | 1.0.0 | هدر افزونه | سفارشی |
| Plugin: mobile-auth | 1.0.0 | هدر افزونه | سفارشی |
| Plugin: woocommerce-setup | 1.3.0 | هدر افزونه | سفارشی |
| Plugin: zarinpal-gateway | 1.1.0 | هدر افزونه | سفارشی |
| Plugin: custom-plugin | 1.1.0 | هدر افزونه | سفارشی |
| Plugin: essential-plugins | 1.1.0 | هدر افزونه | سفارشی |

> هیچ نسخه‌ای «فرض» نشده است؛ هر مقدار به فایل منبع ارجاع دارد. چون WordPress/WooCommerce core در مخزن vendored نشده‌اند، نسخهٔ آن‌ها «هدف استقرار» است نه «کشف‌شده در فایل».

---

## ۲. موجودی آرتیفکت‌های Dev/Test (Artifact Inventory)

| # | مسیر | دسته | حکم |
|---|---|---|---|
| A1 | `docker-compose.yml` | Docker | REMOVE |
| A2 | `docker-compose.production.yml` | Docker | REMOVE |
| A3 | `.github/workflows/production-gate.yml` | CI (test-gate) | REPLACE با pipeline استقرار |
| A4 | `tests/` (۱۶ فایل: shell + python) | Test fixtures/scripts | REMOVE |
| A5 | `staging/` (`README.md`, `provision-catalog.sh`) | Docker staging + seed | REMOVE |
| A6 | `ui-preview/` (index.html, screenshots, scripts) | Dev preview | REMOVE |
| A7 | `setup-bamero.sh` | Dev bootstrap (docker) | REMOVE |
| A8 | `setup-env.sh` | Dev env generator | REMOVE |
| A9 | `.env.export.sh` | Local env loader (termux) | REMOVE |
| A10 | `wp-content/themes/bamero/tools/provision-bamero-catalog.php` | Seed script (کاربران نمونه) | REMOVE |
| A11 | `CODEX_BASELINE.md`, `IMPLEMENTATION_PLAN.md`, `TEST_MATRIX.md`, `STAGING_VERIFICATION.md` | Dev/process docs | REVIEW (نگه‌داشت در docs محافظت‌شده) |

**آرتیفکت‌های واقعی (نگه‌داشت):** `wp-content/**` (قالب + افزونه‌های سفارشی)، `wp-config.php`، `.htaccess`، `robots.txt`، `docs/**`، `README.md`، `.env.example`.

---

## ۳. ماتریس سازگاری (Compatibility Matrix)

| ترکیب | وضعیت | یادداشت |
|---|---|---|
| PHP 8.3 × WP 6.7 | ✅ سازگار | WP 6.7 از PHP 8.3 پشتیبانی می‌کند |
| PHP 8.3 × Woo 9.x | ✅ سازگار | Woo 9.x نیازمند PHP ≥ 7.4؛ 8.3 پشتیبانی‌شده |
| WP 6.7 × MariaDB 10.11 | ✅ سازگار | utf8mb4 پشتیبانی‌شده |
| Woo 9.x × HPOS | ✅ سازگار | اعلام سازگاری در `bamero-zarinpal-gateway.php` |
| Zarinpal v4 × Woo 9.x | ✅ سازگار | request.json/verify.json |
| Theme Bamero 2.0.0 × WP 6.7 | ✅ سازگار | Requires WP 6.0 |
| Mobile-auth × Woo | ✅ سازگار | `Requires Plugins: woocommerce` |

**نتیجهٔ BLOCK:** هیچ ناسازگاری بحرانی یافت نشد → عبور از فاز DISCOVER.

---

## ۴. اسکن اسرار (Secret Scan)

اسکن الگوهای رایج (کلید/رمز/توکن/کلید خصوصی) روی کل درخت: **هیچ سرّ واقعی یافت نشد**. تنها تطابق، ارجاع متغیر `$WP_ADMIN_PASSWORD` در اسکریپت bootstrap است (که خودش حذف خواهد شد). `.gitignore` فایل‌های `.env`, `*.key`, `*.pem` را مستثنا می‌کند.

---

## ۵. اسنپ‌شات پیش از Sanitize (RECOVERY_PROOF)

- git tag: `snapshot/pre-hosting-sanitize`
- آرشیو: `/workspace/bamero-snapshots/pre-hosting-sanitize-2026-09-27.tar.gz`
- sha256: `b695035d5309039b55aca579682bee9bd2cdd1904218c4f7fa87948f5e9cd69d`
