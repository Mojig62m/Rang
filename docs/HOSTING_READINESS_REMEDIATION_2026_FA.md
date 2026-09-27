# گزارش رفع نقص و آماده‌سازی استقرار — بامرو

**نوع سند:** Remediation & Deployment-Readiness Record
**دامنه:** مخزن `Mojig62m/Rang` (شاخهٔ `main`)
**مبنای ورودی:** ممیزی مستقل (AUDIT_REPORT_HOSTING_READINESS_FA.md)

---

## ۱. هدف

این سند، اصلاحات انجام‌شده برای رفع موانع استقرار را مستند می‌کند و جایگزین ادعاهای متناقض پیشین
(`HOSTING_READY_GATE_REPORT_FA.md` که «PRODUCTION_READY» اعلام کرده بود در برابر
`staging_evidence_bundle/deployment-unblocker-blocker.md` که «NO-GO-LIVE» بود) می‌شود.
وضعیت این سند **مرجع واحد** وضعیت کد پس از رفع نقص است.

---

## ۲. خلاصهٔ اصلاحات

| # | نقص | فایل(ها) | اقدام | وضعیت |
|---|-----|----------|-------|-------|
| ۱ | تعریف تکراری `bamero_csp_nonce()` | `themes/bamero/functions.php` | افزودن گارد `function_exists` | ✅ رفع شد |
| ۲ | تعریف تکراری `bamero_security_headers()` | `themes/bamero/functions.php` | افزودن گارد `function_exists` | ✅ رفع شد |
| ۳ | هدرهای امنیتی ناقص در نسخهٔ کانونیک | `plugins/bamero-production-core/...` | افزودن `X-Frame-Options` و `form-action 'self'` | ✅ بهبود یافت |
| ۴ | JSON-LD بدون nonce | `themes/bamero/front-page.php` | افزودن `nonce` (سخت‌سازی دفاعی) | ✅ اعمال شد |
| ۵ | JSON-LD بدون nonce | `themes/bamero/functions.php` | افزودن `nonce` (سخت‌سازی دفاعی) | ✅ اعمال شد |
| ۶ | ارجاع CI به فایل ناموجود `tests/production_gate.sh` | `.github/workflows/production-gate.yml` + `tests/` | ساخت گیت واقعی | ✅ رفع شد |

---

## ۳. اثبات تجربی رفع نقص مرگ‌آور

### ۳.۱ پیش از اصلاح (بازتولید خطا)
```text
STEP 2: loading theme functions.php...
PHP Fatal error:  Cannot redeclare bamero_csp_nonce()
 (previously declared in .../bamero-production-core.php:62)
 in .../themes/bamero/functions.php on line 904
```

### ۳.۲ پس از اصلاح (هارنس `redeclare_harness.php`)
```text
STEP 1: loading bamero-production-core.php (plugin)...
STEP 1 OK. bamero_csp_nonce exists? YES
STEP 2: loading theme functions.php...
STEP 2 OK (no fatal).

RESULT: PASS — plugin + theme loaded together with no redeclare fatal error.
```

### ۳.۳ گیت استاتیک (`tests/production_gate.sh`)
```text
[1/5] PHP syntax lint ......................... done
[2/5] Duplicate function declarations ......... GUARDED (safe) x2 — OK
[3/5] Hard-coded secret scan .................. none found
[4/5] JSON-LD CSP nonce coverage .............. all JSON-LD blocks carry a nonce
[5/5] wp-config fail-closed secret handling ... present
GATE RESULT: PASS
```

---

## ۴. وضعیت باقی‌مانده (نیازمند محیط هاست)

این موارد ذاتاً **وابسته به محیط مقصد** هستند و در سطح مخزن قابل تأیید نهایی نیستند:

- نصب هستهٔ WordPress + WooCommerce (این مخزن یک overlay افزونه/قالب است).
- تأمین دیتابیس واقعی و تمام متغیرهای `.env.example`.
- تأیید سقف‌های PHP هاست (memory_limit / execution_time / upload_max_filesize).
- اجرای تست‌های runtime (OTP، پرداخت زرین‌پال، callback) روی staging.
- پیکربندی Redis object cache و HPOS.

**حکم:** کد از نظر **یکپارچگی و اجراپذیری** آماده است؛ استقرار نهایی مشروط به تأمین موارد بخش ۴ روی هاست مقصد است.
