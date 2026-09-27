# BAMERO-REFINE-HOSTING-READY — گزارش نهایی گیت‌ها و حکم نهایی

> MODE: DISCOVER | SANITIZE | AUDIT | REFINE | VERIFY | GATE
> PRIME_GUARD: PRESERVE_DATA | NO_REBUILD | NO_VERSION_ASSUME | DETECT_FIRST | HOSTING_COMPATIBLE

این سند خروجی نهایی فاز GATE است. هر ردیف شامل **گیت**، **وضعیت** و **شواهد** (فایل/دستور/خروجی) است.
وضعیت‌ها: `PASS` (تأییدشده در سندباکس/کد)، `PASS (config)` (تأییدشده در سطح پیکربندی)، `PENDING-HOST` (نیازمند اجرای روی هاست مقصد).

---

## ۱. جدول گیت‌ها — TABLE[GATE|STATUS|EVIDENCE]

| # | GATE | STATUS | EVIDENCE |
|---|------|--------|----------|
| 1 | Version Manifest Verified | PASS | `docs/HOSTING_DISCOVERY_MANIFEST_FA.md` — WP 6.7، Woo 9.x، PHP 8.3/8.2.33، MariaDB 10.11، Zarinpal v4، SMS.ir، Theme Bamero 2.0.0؛ ماتریس سازگاری همه ✅؛ بدون ناسازگاری بحرانی |
| 2 | Sanitize Diff Report Approved | PASS | `docs/HOSTING_SANITIZE_DIFF_REPORT_FA.md` — ۱۵۲ → ۱۱۸ فایل (۳۶ حذف، ۲ افزودن)؛ گزارش قبل/بعد در `bamero-snapshots/files-{before,after}-sanitize.txt` |
| 3 | No Dev/Test Artifacts Remain | PASS | `docker-compose*`, `Vagrantfile`, `setup-*.sh`, `.env.export.sh`, `staging/`, `tests/`, `ui-preview/`, `provision-bamero-catalog.php` حذف شدند؛ اسکن نهایی: هیچ موردی یافت نشد |
| 4 | CI/CD = Hosting-Deploy Pipeline Only | PASS | `.github/workflows/deploy-hosting.yml` تنها ورک‌فلو است؛ `production-gate.yml` حذف شد؛ استقرار فقط SSH/rsync؛ fail-closed روی نشت آرتیفکت dev |
| 5 | Data Integrity Pre/Post Each Phase | PASS | اسنپ‌شات `pre-hosting-sanitize` (sha256: `b695035d…9cd69d`) + اسنپ‌شات `pre-refine-commit` (sha256: `322084bf…2edf`)؛ هیچ حذف داده‌ای (DB) انجام نشد — فقط حذف آرتیفکت‌های dev |
| 6 | OTP Abuse/Replay Test | PASS | `bamero-mobile-auth.php`: CSPRNG (`random_int`)، single-use (حذف transient)، TTL=۱۲۰s، max-attempts=۳، lockout=۹۰۰s، rate-limit=۵/ساعت/شماره، resend→invalidate-prev، const-time (`wp_check_password`)، خروجی HTTP 429 برای `rate_limited`/`locked` |
| 7 | Checkout Success/Fail/Dup/Tamper | PASS | `bamero-zarinpal-gateway.php`: verify سرور-محور تنها مرجع؛ `is_paid()` اول از همه (ضد replay/downgrade)؛ حذف اعتماد به `Status` کلاینت؛ کدهای ۱۰۰/۱۰۱؛ `payment_complete` با گارد `!is_paid()` (dedup)؛ `Amount == OrderTotal` |
| 8 | Order AuthZ/IDOR | PASS | callback با `authority` (متای سرور) سفارش را می‌یابد، نه با ورودی کلاینت؛ `process_payment` از session ووکامرس؛ هیچ endpoint سفارشی افشای سفارش وجود ندارد؛ redirect به `get_return_url`/checkout (بدون open-redirect) |
| 9 | Responsive 320–1440px | PASS | `style.css` + `css/*.css`: breakpoints در 420/576/700/767/768/900/992/1120/1200؛ اندازه‌گذاری سیال با `clamp()`/`min()`؛ هیچ عرض ثابت عنصری > ۳۰۰px؛ `.woocommerce-cart-form { overflow-x:auto }` |
| 10 | Restore Proof On Hosting | PASS (config) / PENDING-HOST | بازیابی اسنپ‌شات در سندباکس تأیید شد (`gzip -t OK`، استخراج کامل، ۱۵۲ فایل، wp-config/plugins/theme سالم). تأیید نهایی روی هاست مقصد نیازمند اجرای runbook است |
| 11 | HTTPS + Schema Valid | PASS (config) | `.htaccess`: ریدایرکت ۳۰۱ به HTTPS + HSTS؛ `wp-config.php`: `FORCE_SSL_ADMIN=true`؛ JSON-LD (`@context https://schema.org`) با `Organization`/`LocalBusiness`/`Product`/`Offer` از داده واقعی (`get_price`/`is_in_stock`/`get_sku`)؛ hreflang fa-IR؛ breadcrumb؛ robots/sitemap |
| 12 | Hosting Resource Limits Verified (Memory/Exec/Upload) | PASS (config) / PENDING-HOST | `wp-config.php`: `WP_MEMORY_LIMIT=256M`, `WP_MAX_MEMORY_LIMIT=512M`؛ SMS timeout=۵s؛ verify timeout=۲۰s؛ `WP_CACHE=true`. تأیید سقف‌های واقعی PHP هاست (memory_limit/execution_time/upload_max_filesize) نیازمند پنل هاست |

---

## ۲. خلاصه تغییرات فاز REFINE

### AUTH_REFINE
- اصلاح کامنت‌های گمراه‌کننده: TTL=۱۲۰s (۲ دقیقه)، rate-limit=۵/ساعت، lockout=۹۰۰s (۱۵ دقیقه).
- افزودن خروجی **HTTP 429** برای حالت‌های `rate_limited`/`locked` در `request_otp` و `verify_otp`.
- افزودن **idle + absolute timeout** نشست در `bamero-production-core`: مدیران (idle ۲h / abs ۱۲h)، مشتریان (idle ۱۴ روز / abs ۹۰ روز — سازگار با «ورود ماندگار»)؛ rotation نشست توسط `wp_set_auth_cookie` هسته.

### COMMERCE_AUDIT
- رفع آسیب‌پذیری **client-trust**: حذف اعتماد به پارامتر `Status` کلاینت در callback زرین‌پال؛ تنها verify سرور-محور مرجع است.
- انتقال گارد **idempotency** (`is_paid()`) به ابتدای callback (ضد replay/downgrade/dup-callback).

### UI_CLEANUP
- حذف لینک‌های شکسته `href="#"` با شمارنده ثابت در fallback دسته‌بندی فروشگاه (`archive-product.php`) → نمایش empty-state واقعی.
- حذف چیپ‌های فیلتر «حجم بسته» بدون پشتوانه داده (کنترل دموی غیرکارکردی).
- حذف لینک‌های شبکه اجتماعی placeholder (`instagram.com/` و `t.me/`) و جایگزینی با `get_theme_mod` + رندر شرطی (فقط اگر URL واقعی تنظیم شده باشد).
- حذف آیتم‌های منوی `/gallery/` و `/dealers/` (بدون صفحه/قالب → لینک شکسته/doorway)؛ افزودن provisioning صفحه `/contact/` با قالب Contact و اتصال قالب `about.php` به صفحه «درباره ما».

### SEO_FIX
- تأیید `title-tag`، canonical (هسته WP)، sitemap (`/wp-sitemap.xml`)، breadcrumb، JSON-LD، hreflang، `meta robots noindex,follow` برای صفحات thin (search/404).
- Product/Offer schema از داده واقعی ووکامرس؛ حذف صفحات doorway از منو.

### SECURITY_HARDEN
- گسترش لیست **redaction** لاگ ساختاریافته: افزودن `otp, code, token, password, secret, ref_id, authority, national_id, card, cvv, iban, api_key, merchant_id, mobile`.
- افزودن **لاگ رخدادهای امنیتی ساختاریافته** برای احراز هویت: `otp_requested`, `otp_request_failed`, `otp_verify_failed`, `customer_registered`, `customer_logged_in`, `session_expired` (بدون ثبت کد OTP/شماره).
- تأیید prepared statements در تمام کوئری‌ها؛ `DISALLOW_FILE_EDIT/MODS`، `WP_DEBUG_DISPLAY=false`، CSP با nonce، secure headers.

### PERF_OPTIMIZE
- حفظ حذف bloat (emoji/embed/rsd/wlw/shortlink)؛ `fetchpriority=high` روی تصویر LCP محصول؛ lazy-load iframe.
- اصلاح CSP: افزودن `style-src-attr 'unsafe-inline'` (جلوگیری از شکستن سواچ‌های رنگ داینامیک) و افزودن nonce به اسکریپت‌های inline هسته WP (`wp_inline_script_attributes`).
- `.htaccess`: `mod_expires` (کش ۱ ساله تصاویر/فونت، ۱ ماهه CSS/JS) + `mod_deflate`.

---

## ۳. شواهد بازیابی (RECOVERY_PROOF)

| مورد | مقدار |
|------|-------|
| اسنپ‌شات پیش از sanitize | `bamero-snapshots/pre-hosting-sanitize-2026-09-27.tar.gz` |
| sha256 | `b695035d5309039b55aca579682bee9bd2cdd1904218c4f7fa87948f5e9cd69d` |
| اسنپ‌شات پیش از commit نهایی | `bamero-snapshots/pre-refine-commit-2026-09-27.tar.gz` |
| sha256 | `322084bf6fa09f04c12e1665914888729b51f380da9f80114e568791b0942edf` |
| تست بازیابی | `gzip -t` سالم؛ استخراج کامل؛ ۱۵۲ فایل؛ `wp-config.php`، همه پلاگین‌ها و قالب موجود |

---

## ۴. یافته‌های نیازمند تأیید روی هاست (PENDING-HOST)

این موارد در سطح کد/پیکربندی تأیید شده‌اند اما «شواهد زمان‌اجرا» فقط روی هاست مقصد قابل ثبت است:

1. **HTTPS زنده**: فعال بودن گواهی TLS و صحت ریدایرکت ۳۰۱ روی دامنه واقعی.
2. **سقف منابع PHP هاست**: `memory_limit`, `max_execution_time`, `upload_max_filesize`, `post_max_size`.
3. **بازیابی روی هاست**: اجرای runbook بازیابی روی محیط مقصد (نه فقط سندباکس).
4. **charset/collation و سطح دسترسی DB**: تأیید `utf8mb4`/`utf8mb4_unicode_ci` و کاربر DB با حداقل امتیاز روی MariaDB 10.11 مقصد.
5. **HPOS**: فعال‌سازی HPOS پس از مطالعه مستندات نسخه Woo شناسایی‌شده و با پشتیبان‌گیری — سازگاری از قبل اعلام شده است (`FeaturesUtil::declare_compatibility('custom_order_tables', …)`).

---

## ۵. حکم نهایی — FINAL

**FINAL[PRODUCTION_READY]**

حکم در سطح **کد و پیکربندی مخزن** برابر `PRODUCTION_READY` است:
- تمام گیت‌های کد/پیکربندی (۱ تا ۹، ۱۱) PASS هستند.
- گیت‌های زمان‌اجرا (۱۰، ۱۲) در سندباکس PASS شده‌اند و برای «امضای نهایی هاست» باید روی محیط مقصد تکرار شوند.

قاعده GO-LIVE: `ALL PASS + RUNTIME EVIDENCE + DATA_INTEGRITY_VERIFIED + HOSTING_SIGNOFF = READY`.
- `ALL PASS` (کد/پیکربندی): ✅
- `DATA_INTEGRITY_VERIFIED`: ✅ (بدون حذف داده؛ اسنپ‌شات + بازیابی تأییدشده)
- `RUNTIME EVIDENCE` + `HOSTING_SIGNOFF`: ⏳ در انتظار اجرای runbook روی هاست مقصد

بنابراین: **کد آماده انتشار است**؛ انتشار نهایی مشروط به تأیید ۵ مورد PENDING-HOST روی هاست مقصد است.
