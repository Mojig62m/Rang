# گزارش ممیزی تخصصی آمادگی استقرار (Hosting Readiness Audit)
## فروشگاه اینترنتی «بامرو» — مخزن `Mojig62m/Rang` (شاخه `main`)

**نوع سند:** ممیزی فنی مستقل و مستند (Evidence-Based Technical Audit)
**سطح ممیزی:** کارشناس ارشد DevOps / WordPress Security / WooCommerce
**روش:** تحلیل ایستا (Static Analysis) + بازتولید تجربی خطا (Reproducible Runtime Proof) + تطبیق مستندات با واقعیت مخزن
**تاریخ:** بر پایه آخرین کامیت `19f4114` (Merge PR #2 — hosting-ready/refine)

---

## ۱. حکم اجرایی (Executive Verdict)

> ### ❌ نتیجه: **آمادهٔ استقرار مستقیم روی هاست نیست.**
> وضعیت: **NO-GO برای Go-Live** — با دستهٔ «قابل رفع» (Remediable) اما **دارای یک نقص مرگ‌آور اثبات‌شده (Proven Showstopper)**.

این مخزن یک **«لایهٔ افزونه + قالب» (Plugin/Theme Overlay)** روی WordPress است، **نه یک سایت کامل قابل استقرار**. حتی اگر تمام اسرار (secrets) و دیتابیس تأمین شوند، سایت با یک **خطای مرگ‌آور PHP (Fatal Error)** از کار می‌افتد، زیرا دو تابع کلیدی امنیتی در **دو فایل جداگانه بدون گارد `function_exists`** تعریف شده‌اند.

**خلاصهٔ امتیازدهی:**

| حوزه | وضعیت | وزن |
|---|---|---|
| یکپارچگی کد (قابل اجرا بودن) | ❌ **FAIL — خطای مرگ‌آور اثبات‌شده** | حیاتی |
| کامل بودن بستهٔ استقرار (WP core/DB) | ❌ **FAIL — هستهٔ وردپرس و دیتابیس غایب** | حیاتی |
| خط لولهٔ CI/CD | ❌ **FAIL — ارجاع به فایل ناموجود** | بالا |
| انسجام مستندات | ❌ **FAIL — تناقض داخلی آشکار** | متوسط |
| سازگاری CSP با JSON-LD | ⚠️ **WARN — دو نقطهٔ نقض غیرسازگار** | متوسط |
| معماری امنیتی (طراحی) | ✅ **PASS — طراحی بسیار قوی و اصولی** | بالا |
| مدیریت اسرار (Secrets) | ✅ **PASS — هیچ secret هاردکد‌شده‌ای یافت نشد** | حیاتی |

---

## ۲. نقص مرگ‌آور اثبات‌شده (PROVEN SHOWSTOPPER)

### ۲.۱ شرح فنی

دو تابع حیاتی امنیتی در دو محل مستقل و **بدون گارد `function_exists()`** تعریف شده‌اند:

| تابع | محل تعریف اول | محل تعریف دوم (تکراری) |
|---|---|---|
| `bamero_csp_nonce()` | `wp-content/plugins/bamero-production-core/bamero-production-core.php` خط **۶۲** | `wp-content/themes/bamero/functions.php` خط **۹۰۴** |
| `bamero_security_headers()` | `wp-content/plugins/bamero-production-core/bamero-production-core.php` خط **۹۳** | `wp-content/themes/bamero/functions.php` خط **۹۱۲** |

از آنجا که افزونه **پیش از** قالب بارگذاری می‌شود، هنگام بارگذاری `functions.php` قالب، PHP تلاش می‌کند تابعی را که پیش‌تر تعریف شده دوباره تعریف کند و **پردازش با خطای مرگ‌آور متوقف می‌شود**.

### ۲.۲ اثبات تجربی (Reproducible Proof)

برای اثبات قطعی — و نه فقط ادعای نظری — یک هارنس (harness) با استاب‌کردن توابع وردپرس ساخته و **ترتیب واقعی بارگذاری** (افزونه → قالب) بازتولید شد:

**فایل هارنس:** `redeclare_harness.php`

```php
// ترتیب واقعی بوت‌استرپ وردپرس: افزونه‌ها قبل از قالب بارگذاری می‌شوند
require 'wp-content/plugins/bamero-production-core/bamero-production-core.php'; // STEP 1
require 'wp-content/themes/bamero/functions.php';                              // STEP 2
```

**خروجی واقعی اجرا:**

```text
STEP 1: loading bamero-production-core.php (plugin)...
STEP 1 OK. bamero_csp_nonce exists? YES
STEP 2: loading theme functions.php...
PHP Fatal error:  Cannot redeclare bamero_csp_nonce()
  (previously declared in
   /workspace/rang-repo/wp-content/plugins/bamero-production-core/bamero-production-core.php:62)
  in /workspace/rang-repo/wp-content/themes/bamero/functions.php on line 904
```

### ۲.۳ پیامد

- این خطا **قطعاً** روی هر هاست استاندارد (PHP 8.2/8.3) رخ می‌دهد و **کل سایت (Frontend + wp-admin)** را از کار می‌اندازد.
- جالب توجه: خودِ کد در جاهای دیگر **از گارد استفاده کرده** — مثلاً `wp-content/themes/bamero/index.php:53` و `bamero-mobile-auth.php:494` هر دو الگوی صحیح `function_exists('bamero_csp_nonce')` را به‌کار برده‌اند. یعنی توسعه‌دهنده از این ریسک آگاه بوده اما در دو نقطهٔ بحرانی اعمال نکرده است.
- **این تنها یک هشدار نیست؛ یک خطای مرگ‌آور است که با اجرای واقعی کد اثبات شده است.**

### ۲.۴ راه‌حل پیشنهادی (Fix)

دور هر یک از چهار تعریف تکراری، گارد اضافه شود:

```php
if ( ! function_exists( 'bamero_csp_nonce' ) ) {
    function bamero_csp_nonce() { /* ... */ }
}
if ( ! function_exists( 'bamero_security_headers' ) ) {
    function bamero_security_headers() { /* ... */ }
}
```

یا حذف کامل نسخهٔ تکراری از `functions.php` قالب و واگذاری مسئولیت به افزونهٔ هستهٔ تولید.

---

## ۳. کامل نبودن بستهٔ استقرار (Deployment Bundle Incompleteness)

مخزن **فقط** شامل `wp-config.php`، `.htaccess`، `robots.txt`، مستندات و پوشهٔ `wp-content` است. اجزای ضروری یک سایت وردپرس **غایب** هستند:

| جزء | وضعیت | اهمیت |
|---|---|---|
| `wp-admin/` | ❌ غایب | حیاتی — بدون آن پنل مدیریت وجود ندارد |
| `wp-includes/` | ❌ غایب | حیاتی — هستهٔ وردپرس |
| `wp-login.php` | ❌ غایب | حیاتی — ورود مدیر |
| `index.php` (ریشه) | ❌ غایب | حیاتی — نقطهٔ ورود |
| `wp-settings.php` | ❌ غایب | حیاتی — بوت‌استرپ |
| دیتابیس (`.sql` export) | ❌ غایب | حیاتی — بدون آن هیچ محصول/تنظیمی نیست |
| `wp-content/uploads/` | ❌ غایب | بالا — تصاویر محصولات |
| افزونهٔ WooCommerce | ❌ غایب | حیاتی — قلب فروشگاه |
| افزونهٔ YITH Wishlist | ❌ غایب | متوسط — در README ادعا شده |
| افزونهٔ WP Super Cache | ❌ غایب | متوسط — در README ادعا شده |

**نتیجه:** این مخزن **قابل استقرار به‌تنهایی نیست**. باید ابتدا یک نصب کامل وردپرس + WooCommerce روی هاست انجام شود، سپس این لایه روی آن قرار گیرد، سپس دیتابیس و اسرار تأمین شوند. هر مستندی که این بسته را «آمادهٔ استقرار» بنامد، **ناقص و گمراه‌کننده** است.

---

## ۴. خط لولهٔ CI/CD شکسته (Broken CI Pipeline)

فایل `.github/workflows/production-gate.yml` در مرحلهٔ «Production gate» این دستور را اجرا می‌کند:

```yaml
- name: Production gate
  run: bash tests/production_gate.sh
```

اما **پوشهٔ `tests/` در مخزن وجود ندارد**:

```text
$ ls tests
ls: cannot access 'tests': No such file or directory
```

همچنین سند `docs/HOSTING_READY_GATE_REPORT_FA.md` (گیت #۴) ادعا می‌کند:

> «`.github/workflows/deploy-hosting.yml` تنها ورک‌فلو است؛ `production-gate.yml` حذف شد»

در حالی که واقعیت دقیقاً برعکس است:

```text
$ ls .github/workflows/
production-gate.yml          # ← تنها فایل موجود
$ ls .github/workflows/deploy-hosting.yml
ls: cannot access '...deploy-hosting.yml': No such file or directory
```

**نتیجه:** خط لولهٔ CI نه‌فقط شکسته است (به فایل ناموجود ارجاع می‌دهد و در اولین اجرا `FAIL` می‌شود)، بلکه مستندات دربارهٔ آن **خلاف واقعیت** گزارش داده‌اند.

---

## ۵. تناقض داخلی مستندات (Documentation Self-Contradiction)

مستندات مخزن دو روایت **متضاد** از آمادگی ارائه می‌دهند:

| سند | ادعا |
|---|---|
| `docs/HOSTING_READY_GATE_REPORT_FA.md` | «FINAL / PRODUCTION_READY» — ۱۲ گیت PASS |
| `docs/PRODUCTION_READINESS_FINAL_2026_FA.md` | «Release Candidate آمادهٔ ورود به staging»؛ Go-Live **مشروط** |
| `docs/staging_evidence_bundle/deployment-unblocker-blocker.md` | **`HALTED — PREREQUISITE NOT AVAILABLE`** و **`CONDITIONAL / NO-GO-LIVE`** |

سند سوم صریحاً می‌گوید هیچ محیط staging واقعی وجود نداشت:

```text
PHP: PHP 8.3.6
wp: not installed
redis-cli: not installed
wpscan: not installed
STAGING_URL: absent
SSH_* variables: absent
DB_* variables: absent
REDIS_* variables: absent
```

**نتیجه:** ادعای «PRODUCTION_READY» در گیت‌ریپورت، **با شواهد خودِ مخزن متناقض است**. یک ممیز حرفه‌ای نمی‌تواند بر چنین مستنداتی اتکا کند. این تناقض به‌تنهایی یک ریسک حاکمیتی (Governance Risk) جدی است.

---

## ۶. ناسازگاری CSP با JSON-LD (Content Security Policy Conflict)

سیاست امنیتی محتوا (CSP) در `bamero_security_headers()` با `script-src 'self' 'nonce-...'` اعمال می‌شود. اما دو نقطهٔ خروجی JSON-LD **فاقد nonce** هستند:

| فایل | خط | وضعیت |
|---|---|---|
| `wp-content/themes/bamero/front-page.php` | ۱۶ | ❌ `<script type="application/ld+json">` بدون nonce |
| `wp-content/themes/bamero/functions.php` | ۸۳۴ | ❌ بدون nonce |
| `wp-content/themes/bamero/index.php` | ۵۳ | ✅ دارای nonce (صحیح) |

```php
// front-page.php:16 — بدون nonce
<script type="application/ld+json"><?php echo wp_json_encode($schema, ...); ?></script>

// functions.php:834 — بدون nonce
echo '<script type="application/ld+json">' . wp_json_encode($graph, ...) . '</script>';
```

**پیامد:** با CSP سخت‌گیرانه، این بلوک‌های Schema.org توسط مرورگر **مسدود** می‌شوند و داده‌های ساختاریافته (Rich Results گوگل) از بین می‌رود — یعنی SEO محصولات آسیب می‌بیند. این دقیقاً همان چیزی است که مستندات مدعی «SEO_FIX / Schema Valid» بودن آن هستند.

**راه‌حل:** افزودن `nonce="<?php echo esc_attr( bamero_csp_nonce() ); ?>"` به هر دو نقطه.

---

## ۷. نقاط قوت تأییدشده (Verified Strengths)

انصاف حکم می‌کند که معماری امنیتی این پروژه **واقعاً قوی و اصولی** طراحی شده است. این موارد به‌صورت مستقل در کد تأیید شدند (نه صرفاً بر اساس مستندات):

### ۷.۱ مدیریت اسرار (Secrets Management) — ✅ عالی
- `wp-config.php` از الگوی **fail-closed** استفاده می‌کند: تابع `bamero_require_env()` مقادیر placeholder را رد می‌کند و در صورت نبود متغیر محیطی، با **HTTP 500** متوقف می‌شود.
- **جستجوی مستقل برای secret هاردکد‌شده نتیجه‌ای نداشت** — هیچ رمز/کلید/Merchant ID داخل کد یا دیتابیس جاسازی نشده است.
- `.env.example` تنها متغیرهایی را فهرست می‌کند که کد واقعاً می‌خواند (DB، ۸ نمک، SMS.ir، Zarinpal، `BAMERO_PAYMENT_WEBHOOK_SECRET`).

### ۷.۲ امنیت درگاه پرداخت (Zarinpal Gateway) — ✅ قوی
- تأیید **سمت‌سرور** (server-side verify) به‌عنوان تنها مرجع؛ عدم اعتماد به پارامتر `Status` کلاینت.
- گارد **idempotency** (`$order->is_paid()`) در ابتدای callback → مقاوم در برابر replay/downgrade/dup-callback.
- تطبیق `Amount == OrderTotal` و پذیرش صرف کدهای `100/101`.

### ۷.۳ احراز هویت موبایل (OTP) — ✅ سخت‌سازی‌شده
- تولید OTP با `random_int()` (CSPRNG)، ذخیره‌سازی با `wp_hash_password()`، بررسی constant-time با `wp_check_password()`.
- TTL=۱۲۰s، سقف تلاش=۳، lockout=۹۰۰s، rate-limit=۵/ساعت/شماره + IP + global.
- سیاست **email-free** و ایمیل مصنوعی غیرقابل‌مسیریابی `hash('sha256', phone+salt).'@bamero.internal'`.

### ۷.۴ سخت‌سازی زیرساخت — ✅
- `.htaccess`: مسدودسازی `xmlrpc.php`، `wp-config.php`، پوشهٔ `docs/`، فایل‌های `.md/.sh/.yml`؛ ریدایرکت ۳۰۱ به HTTPS + HSTS.
- `wp-config.php`: `DISALLOW_FILE_EDIT`، `DISALLOW_FILE_MODS`، `FORCE_SSL_ADMIN`، `WP_CACHE`، محدودیت حافظه.
- استفاده از prepared statements و nonce در REST/فرم‌ها؛ عدم وجود inline event handler (سازگار با CSP).

---

## ۸. چک‌لیست پیش از استقرار (Pre-Deployment Checklist)

موارد زیر **به‌ترتیب اولویت** باید پیش از هر استقراری رفع شوند:

| # | اقدام | اولویت | وضعیت |
|---|---|---|---|
| ۱ | رفع تعریف تکراری `bamero_csp_nonce()` و `bamero_security_headers()` (گارد `function_exists` یا حذف نسخهٔ تکراری) | 🔴 بحرانی | ❌ |
| ۲ | افزودن nonce به JSON-LD در `front-page.php:16` و `functions.php:834` | 🟠 بالا | ❌ |
| ۳ | نصب کامل هستهٔ WordPress + WooCommerce روی هاست (این مخزن overlay است) | 🔴 بحرانی | ❌ |
| ۴ | تأمین دیتابیس واقعی (محصولات، تنظیمات، برگه‌ها) | 🔴 بحرانی | ❌ |
| ۵ | اصلاح یا حذف ارجاع CI به `tests/production_gate.sh` ناموجود | 🟠 بالا | ❌ |
| ۶ | یکسان‌سازی مستندات متناقض (PRODUCTION_READY در برابر NO-GO-LIVE) | 🟡 متوسط | ❌ |
| ۷ | تأمین تمام متغیرهای محیطی `.env.example` (DB، نمک‌ها، SMS.ir، Zarinpal) | 🔴 بحرانی | ⏳ وابسته به هاست |
| ۸ | اجرای واقعی روی staging و ثبت شواهد runtime (تست OTP، پرداخت، callback) | 🟠 بالا | ⏳ انجام‌نشده |
| ۹ | تأیید سقف‌های PHP هاست (memory_limit / execution_time / upload_max_filesize) | 🟡 متوسط | ⏳ وابسته به هاست |
| ۱۰ | پیکربندی Redis object cache و تست HPOS | 🟡 متوسط | ⏳ وابسته به هاست |

---

## ۹. جمع‌بندی نهایی (Final Conclusion)

مخزن `Mojig62m/Rang` یک **پایهٔ مهندسی‌شدهٔ باکیفیت** است: معماری امنیتی آن (fail-closed secrets، idempotency پرداخت، سخت‌سازی OTP، هدرهای امنیتی) در سطحی فراتر از میانگین پروژه‌های فروشگاهی فارسی قرار دارد و هیچ secret هاردکد‌شده‌ای در آن یافت نشد.

**با این حال، پاسخ صریح به پرسش کاربر این است:**

> **خیر، این سایت در وضعیت فعلی آمادهٔ استقرار روی هاست نیست.**

دلایل مستند و اثبات‌شده:

1. **نقص مرگ‌آور اثبات‌شده:** تعریف تکراری دو تابع امنیتی، با اجرای واقعی کد خطای `Cannot redeclare bamero_csp_nonce()` را تولید می‌کند و کل سایت را از کار می‌اندازد. (تنها یک خط گارد `function_exists` فاصله تا رفع.)
2. **بستهٔ استقرار ناقص:** هستهٔ وردپرس، دیتابیس، uploads و افزونه‌های شخص‌ثالث (WooCommerce، YITH، WP Super Cache) غایب‌اند.
3. **CI/CD شکسته:** ارجاع به `tests/production_gate.sh` ناموجود؛ `deploy-hosting.yml` ادعاشده اصلاً وجود ندارد.
4. **مستندات متناقض:** ادعای «PRODUCTION_READY» با شواهد خودِ مخزن («HALTED — NO-GO-LIVE») در تضاد است.
5. **ناسازگاری CSP:** دو خروجی JSON-LD بدون nonce مسدود می‌شوند و SEO را تضعیف می‌کنند.

**مسیر پیشنهادی:** این پروژه در وضعیت **«Release Candidate با یک باگ بحرانی»** است. با رفع مورد ۱ (چند دقیقه کار) و سپس طی مراحل ۲ تا ۱۰، ظرفیت رسیدن به وضعیت **Go-Live** را دارد. توصیهٔ اکید: **پیش از استقرار، حتماً روی محیط staging اجرا و شواهد runtime ثبت شود** — همان چیزی که خودِ سند `deployment-unblocker-blocker.md` صادقانه اعلام کرده که هرگز انجام نشده است.

---

### پیوست: روش‌شناسی و شواهد خام

- **ابزارها:** `php 8.2.33` (lint + اجرای هارنس)، `grep`، `find`، `git log`.
- **آماره‌های مخزن:** ۲۲ فایل PHP، ۳٬۸۶۹ خط کد PHP، ۳۶ سند در پوشهٔ `docs/`، حجم کل ~۴.۴MB.
- **فایل‌های کلیدی بررسی‌شده:** `wp-config.php`، `.htaccess`، `.env.example`، `bamero-production-core.php`، `bamero-zarinpal-gateway.php`، `bamero-mobile-auth.php`، `themes/bamero/functions.php`، `front-page.php`، `index.php`، `.github/workflows/production-gate.yml` و اسناد `docs/`.
- **اثبات تجربی:** هارنس `redeclare_harness.php` (ترتیب واقعی بوت‌استرپ افزونه→قالب).
- **کنترل منفی:** جستجوی الگوهای secret هاردکد‌شده → نتیجه: صفر مورد.
