# سند اعتبارسنجی علمی و فنی استقرار (Scientific & Technical Deployment Validation)
## فروشگاه اینترنتی «بامرو» — مخزن `Mojig62m/Rang`

**نوع سند:** Evidence-Based Deployment Validation
**روش‌شناسی:** تحلیل ایستا + اثبات تجربی runtime + استناد به منابع مرجع اولیه (Primary Sources)
**تاریخ مرجع:** مطابق آخرین وضعیت مخزن پس از اصلاحات (PR #3)
**پیوست اجرایی:** Pull Request → https://github.com/Mojig62m/Rang/pull/3

---

## ۰. خلاصهٔ حکم نهایی

| پرسش | پاسخ مستند |
|---|---|
| آیا کد پس از اصلاح، از نظر یکپارچگی و اجراپذیری آماده است؟ | ✅ **بله** — نقص مرگ‌آور اثبات‌شده رفع شد و با هارنس runtime اثبات گردید |
| آیا بستهٔ مخزن به‌تنهایی روی هاست قابل استقرار است؟ | ⚠️ **خیر** — این مخزن یک «لایهٔ افزونه/قالب» است و نیازمند نصب هستهٔ WordPress + WooCommerce و تأمین دیتابیس/اسرار است |
| آیا گیت CI عبور می‌کند؟ | ✅ **بله** — `GATE RESULT: PASS` |
| آیا معماری امنیتی قابل دفاع است؟ | ✅ **بله** — منطبق با OWASP و منابع مرجع |

**حکم نهایی:** کد از نظر **فنی و مهندسی آمادهٔ ورود به staging** است؛ **Go-Live نهایی** مشروط به تأمین پیش‌نیازهای محیط هاست (بخش ۵) است.

---

## ۱. روش‌شناسی و منابع مرجع

این اعتبارسنجی بر پایهٔ **منابع اولیهٔ معتبر** (نه وبلاگ‌های ثانویه) بنا شده است. برای هر ادعا، منبع مرجع ذکر می‌شود:

| # | منبع | مرجع (URL) | کاربرد |
|---|------|-----------|--------|
| R1 | PHP Manual — `function_exists()` | https://www.php.net/manual/en/function.function-exists.php | اثبات راهکار گارد تابع |
| R2 | PHP Manual — User-defined functions (redeclaration) | https://www.php.net/manual/en/functions.user-defined.php | اثبات ماهیت مرگ‌آور تعریف تکراری |
| R3 | WordPress Developer — `plugins_loaded` Hook | https://developer.wordpress.org/reference/hooks/plugins_loaded/ | اثبات ترتیب بارگذاری |
| R4 | WordPress VIP — Load order of plugins | https://docs.wpvip.com/plugins/load-order/ | اثبات تقدم افزونه بر قالب |
| R5 | WordPress Plugin Handbook — Best Practices | https://developer.wordpress.org/plugins/plugin-basics/best-practices/ | استاندارد `function_exists` برای توابع pluggable |
| R6 | MDN — CSP `script-src` | https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/script-src | سیاست اسکریپت و nonce |
| R7 | OWASP — Content Security Policy Cheat Sheet | https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html | استاندارد nonce و CSP سخت‌گیرانه |
| R8 | W3C — CSP Level 3 (Recommendation) | https://www.w3.org/TR/CSP3/ | تعریف رسمی رفتار data block |
| R9 | Mathias Bynens (Chrome/Google) — JSON data blocks & CSP | https://mathiasbynens.be/notes/json-dom-csp | اثبات رفتار JSON-LD در برابر CSP |
| R10 | schema.org | https://schema.org/ | اعتبار داده ساختاریافته |
| R11 | Google Search Central — Structured Data | https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data | ارزش SEO داده ساختاریافته |
| R12 | WooCommerce — HPOS (developer docs) | https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage | سازگاری HPOS |
| R13 | WooCommerce — Payment Gateway API | https://developer.woocommerce.com/docs/features/payments/payment-gateway-api/ | صحت پیاده‌سازی درگاه |
| R14 | OWASP ASVS — Secrets Management | https://owasp.org/www-project-application-security-verification-standard/ | مدیریت اسرار |
| R15 | ZarinPal Official Docs | https://docs.zarinpal.com/paymentGateway/ | صحت چرخهٔ request/verify |

---

## ۲. اثبات علمی رفع نقص مرگ‌آور (Showstopper)

### ۲.۱ مبنای نظری

- **R2 (PHP Manual):** تعریف دوبارهٔ یک تابع در PHP یک خطای **compile-time fatal** است: `Fatal error: Cannot redeclare ...`.
- **R3/R4 (WordPress):** ترتیب بوت‌استرپ وردپرس **ابتدا افزونه‌ها، سپس قالب** است. یعنی هر تابعی که افزونه تعریف کند، پیش از بارگذاری `functions.php` قالب موجود است.
- **R1/R5 (PHP + WP Best Practices):** راهکار استاندارد، محصورکردن تعریف در `if ( ! function_exists( ... ) )` است.

### ۲.۲ مبنای تجربی (Empirical Proof)

**الف) بازتولید خطا پیش از اصلاح** — هارنس `tests/redeclare_harness.php` با ترتیب واقعی افزونه→قالب:
```text
STEP 2: loading theme functions.php...
PHP Fatal error:  Cannot redeclare bamero_csp_nonce()
 (previously declared in .../bamero-production-core.php:62)
 in .../themes/bamero/functions.php on line 904
```

**ب) اثبات رفع پس از اصلاح** (اجرای واقعی، خروجی واقعی):
```text
STEP 1: loading bamero-production-core.php (plugin)...
STEP 1 OK. bamero_csp_nonce exists? YES
STEP 2: loading theme functions.php...
STEP 2 OK (no fatal).

RESULT: PASS — plugin + theme loaded together with no redeclare fatal error.
```

**نتیجهٔ قطعی:** نقص مرگ‌آور **رفع شد** و با اجرای واقعی کد (نه ادعای نظری) اثبات گردید.

### ۲.۳ گیت استاتیک (CI Gate)
```text
[1/5] PHP syntax lint ......................... done
[2/5] Duplicate function declarations ......... GUARDED (safe) x2 — OK
[3/5] Hard-coded secret scan .................. none found
[4/5] JSON-LD CSP nonce coverage .............. all JSON-LD blocks carry a nonce
[5/5] wp-config fail-closed secret handling ... present
GATE RESULT: PASS
```

---

## ۳. اعتبارسنجی معماری امنیتی در برابر استانداردها

### ۳.۱ مدیریت اسرار (Secrets) — منطبق با OWASP ASVS (R14)
- `wp-config.php` با الگوی **fail-closed** (`bamero_require_env`) کار می‌کند؛ در نبود متغیر محیطی، با HTTP 500 متوقف می‌شود.
- جستجوی مستقل برای الگوهای secret هاردکد‌شده: **صفر مورد**.
- تمام کلیدها (DB، ۸ نمک، SMS.ir، Zarinpal) از محیط خوانده می‌شوند.
- **حکم:** منطبق با اصل «عدم نگهداری اسرار در کد» (OWASP ASVS V6/V14).

### ۳.۲ درگاه پرداخت زرین‌پال — منطبق با WooCommerce Gateway API (R13) و ZarinPal Docs (R15)
- چرخهٔ `request → StartPay → callback → verify` مطابق مستندات رسمی زرین‌پال v4.
- **verify سمت‌سرور** تنها مرجع؛ عدم اعتماد به پارامتر `Status` کلاینت (دفاع در برابر دستکاری).
- گارد **idempotency** (`$order->is_paid()`) در ابتدای callback → مقاوم در برابر replay و dup-callback.
- تطبیق `Amount == OrderTotal` و پذیرش صرف کدهای `100/101`.
- **حکم:** منطبق با الزامات Gateway API و اصول دفاعی.

### ۳.۳ احراز هویت موبایل (OTP)
- تولید با `random_int()` (CSPRNG)، ذخیرهٔ هش با `wp_hash_password()`، بررسی constant-time با `wp_check_password()`.
- TTL=۱۲۰s، سقف تلاش=۳، lockout=۹۰۰s، rate-limit=۵/ساعت/شماره (+IP+global).
- سیاست email-free با ایمیل مصنوعی غیرقابل‌مسیریابی.
- **حکم:** سخت‌سازی فراتر از میانگین پروژه‌های فروشگاهی فارسی.

### ۳.۴ سازگاری HPOS (R12)
- درگاه و افزونه‌های تجاری، سازگاری HPOS را اعلام می‌کنند → منطبق با مسیر رسمی WooCommerce.

---

## ۴. تصحیح علمی مهم: رفتار JSON-LD در برابر CSP

در ممیزی اولیه، نبودِ `nonce` روی بلوک‌های `application/ld+json` به‌عنوان «مسدودشدن توسط CSP» گزارش شده بود. برای رعایت دقت علمی، این ادعا **تصحیح** می‌شود:

- **R8 (W3C CSP3) و R9 (Bynens، مهندس Chrome گوگل):** یک عنصر `<script>` با `type` **غیر‌JavaScript** (مانند `application/ld+json`) یک **data block** است که مرورگر آن را **اجرا نمی‌کند**؛ بنابراین **تحت `script-src` قرار نمی‌گیرد و مسدود نمی‌شود**.
- **R6 (MDN):** `script-src` منابع «JavaScript» را کنترل می‌کند؛ inline scriptهای اجراشدنی نیازمند nonce/hash هستند.

**نتیجهٔ اصلاح‌شده:** افزودن `nonce` به JSON-LD **یک الزام عملکردی نبود**، بلکه یک **سخت‌سازی دفاعی (defensive hardening)** است که:
1. بی‌خطر است و هیچ رفتاری را نمی‌شکند،
2. با انتظار برخی ابزارهای ممیزی و سیاست‌های سخت‌گیرانهٔ سازمانی هم‌راستا است،
3. یکنواختی با سایر بلوک‌های اسکریپت را تضمین می‌کند.

این تصحیح، **اعتبار سایر یافته‌ها را خدشه‌دار نمی‌کند**؛ نقص مرگ‌آور (بخش ۲) مستقل و قطعی است.

---

## ۵. پیش‌نیازهای باقی‌ماندهٔ محیط هاست (خارج از دامنهٔ کد)

این موارد ذاتاً **وابسته به محیط مقصد** هستند و در سطح مخزن قابل تأیید نهایی نیستند:

| # | پیش‌نیاز | مرجع/دلیل |
|---|----------|-----------|
| ۱ | نصب هستهٔ WordPress (6.7.x) + WooCommerce (9.x) | مخزن overlay است |
| ۲ | تأمین دیتابیس واقعی (محصولات/تنظیمات/برگه‌ها) | بدون آن سایت خالی است |
| ۳ | تنظیم تمام متغیرهای `.env.example` | R14 — مدیریت اسرار |
| ۴ | تأیید سقف‌های PHP هاست (memory_limit/execution_time/upload_max_filesize) | الزام runtime |
| ۵ | دامنه + گواهی HTTPS معتبر | الزام HSTS/CSP |
| ۶ | اجرای تست‌های runtime روی staging (OTP، پرداخت، callback) | R15 — صحت درگاه |
| ۷ | پیکربندی Redis object cache + تست HPOS | R12 |

---

## ۶. فهرست کنترل نهایی (Final Verification Matrix)

| حوزه | معیار | روش تأیید | نتیجه |
|------|-------|-----------|-------|
| یکپارچگی کد | بدون خطای مرگ‌آور | هارنس runtime | ✅ PASS |
| Syntax | تمام فایل‌های PHP | `php -l` | ✅ PASS |
| توابع تکراری | بدون تعریف ناایمن | گیت guard-aware | ✅ PASS |
| اسرار | بدون secret هاردکد | اسکن الگو | ✅ PASS |
| CSP | پوشش nonce اسکریپت‌ها | گیت + MDN R6 | ✅ PASS |
| امنیت پرداخت | verify سمت‌سرور + idempotency | بازبینی کد + R13/R15 | ✅ PASS |
| احراز هویت | OTP سخت‌شده | بازبینی کد | ✅ PASS |
| HPOS | اعلام سازگاری | بازبینی کد + R12 | ✅ PASS |
| استقرار کامل | WP core + DB + env | نیازمند هاست | ⏳ PENDING-HOST |

---

## ۷. منابع (References)

1. PHP Manual — `function_exists()`: https://www.php.net/manual/en/function.function-exists.php
2. PHP Manual — User-defined functions: https://www.php.net/manual/en/functions.user-defined.php
3. WordPress Developer — `plugins_loaded`: https://developer.wordpress.org/reference/hooks/plugins_loaded/
4. WordPress VIP — Plugin load order: https://docs.wpvip.com/plugins/load-order/
5. WordPress Plugin Handbook — Best Practices: https://developer.wordpress.org/plugins/plugin-basics/best-practices/
6. MDN — CSP `script-src`: https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/script-src
7. OWASP — CSP Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html
8. W3C — CSP Level 3: https://www.w3.org/TR/CSP3/
9. Mathias Bynens — JSON data blocks & CSP: https://mathiasbynens.be/notes/json-dom-csp
10. schema.org: https://schema.org/
11. Google Search Central — Structured Data: https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data
12. WooCommerce — HPOS: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage
13. WooCommerce — Payment Gateway API: https://developer.woocommerce.com/docs/features/payments/payment-gateway-api/
14. OWASP ASVS: https://owasp.org/www-project-application-security-verification-standard/
15. ZarinPal Official Docs: https://docs.zarinpal.com/paymentGateway/

---

## ۸. جمع‌بندی

پس از رفع نقص مرگ‌آور و اصلاح گیت CI، کد بامرو از نظر **یکپارچگی، اجراپذیری و معماری امنیتی** با استناد به منابع مرجع (PHP، WordPress، MDN، OWASP، W3C، WooCommerce، ZarinPal) **اعتبارسنجی شد**. استقرار نهایی روی هاست، مشروط به تأمین پیش‌نیازهای محیطی بخش ۵ است — که ماهیتاً خارج از دامنهٔ مخزن کد قرار دارند و باید روی هاست مقصد اجرا و ثبت شوند.
