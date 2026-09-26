# گزارش مستقل آمادگی استقرار بامرو — ۲۰۲۶

**نسخه:** 1.0.0  
**دامنه:** Theme سفارشی فارسی/RTL برای WordPress و WooCommerce  
**روش:** بازبینی کد، اجرای lint و گیت‌های ایستا در sandbox؛ بدون جعل پاسخ سرویس یا شبیه‌سازی موفقیت محیط production.

## نتیجه اجرایی

> **وضعیت نهایی: CONDITIONAL / STAGING-READY — NO-GO-LIVE تا تکمیل گیت‌های محیطی.**

این نتیجه یک محدودیت علمی است، نه نقص در بسته‌بندی: بدون WordPress runtime واقعی، پایگاه‌داده staging، نسخه‌های نهایی افزونه‌ها، provider پیامک، درگاه پرداخت، URL عمومی، دادهٔ میدانی Core Web Vitals و مجوز عملیات، صدور گواهی «کاملاً آمادهٔ Go-Live» قابل اثبات نیست. بنابراین این بسته، کل سورس و روش اثبات را تحویل می‌دهد، اما ادعای موفقیت آزمون‌هایی را که اجرا نشده‌اند مطرح نمی‌کند.

## شواهد قابل بازتولید در این تحویل

| کنترل | نتیجه | روش/شاهد |
|---|---:|---|
| PHP syntax روی فایل‌های اجرایی | PASS | `php -l` از طریق `tests/production_gate.sh` |
| کنترل‌های امنیتی ایستا | PASS | `tests/static_checks.sh` |
| UI/UX ایستا، فونت محلی، RTL و reduced motion | PASS | `tests/ui_ux_static_checks.sh` |
| مرز بدون transport ایمیل | PASS | `tests/email_free_static_checks.sh` |
| محدودیت seed مصنوعی | PASS | `tests/seed-count-check.sh` |
| قرارداد Theme | PASS کدی | `wp-content/themes/bamero/theme.json` و `css/variables.css` |
| JSON و نبود الگوهای متداول secret | PASS | `tests/production_gate.sh` |
| WordPress/WooCommerce runtime | NOT RUN | staging لازم است |
| پرداخت واقعی و callback provider | NOT RUN | credential و endpoint لازم است |
| ارسال SMS، timeout و retry واقعی | NOT RUN | provider تأییدشده لازم است |
| Lighthouse/axe/keyboard روی URL واقعی | NOT RUN | URL staging و browser لازم است |
| backup/restore و rollback | NOT RUN | زیرساخت عملیاتی لازم است |

## تصمیم‌های معماری و تکنیک‌های قابل استفاده

**Design contract:** `theme.json` رنگ، تایپوگرافی، scale فاصله و layout را به‌صورت مرکزی تعریف می‌کند و CSS tokenها در `css/variables.css` جزئیات runtime را پوشش می‌دهند. این الگو از پراکندگی مقدارها جلوگیری می‌کند و با راهنمای رسمی WordPress برای global settings/styles هم‌راستا است [1].

**SSR-first و progressive enhancement:** HTML مهم از PHP و WooCommerce رندر می‌شود؛ JavaScript فقط تعاملات محلی مانند منوی موبایل، تب‌ها، ماشین‌حساب و به‌روزرسانی cart را تقویت می‌کند. این تصمیم، شکست JavaScript را به شکست کامل مسیر خرید تبدیل نمی‌کند.

**قرارداد معنایی DOM:** تعاملات به کلاس‌ها و نشانه‌های مشخص Theme/WooCommerce متکی هستند و selector مشترک cart در هسته production تعریف شده است. تغییر markup باید با گیت selector همراه باشد.

**طراحی state و accessibility:** focus-visible، skip link، `aria-expanded`، `aria-live`، labelهای فرم، targetهای لمسی و `prefers-reduced-motion` در کد و CSS لحاظ شده‌اند. معیار مرجع WCAG 2.2، focus قابل مشاهده و Target Size (Minimum) را به‌عنوان معیارهای رسمی معرفی می‌کند [2]. بااین‌حال PASS کامل دسترسی‌پذیری فقط با آزمون صفحهٔ واقعی و صفحه‌خوان قابل صدور است.

**Performance architecture:** فونت محلی WOFF2، preload محدود برای صفحات حیاتی، ابعاد تصویر، enqueueهای وابسته به context و کاهش CDN خارجی اعمال شده‌اند. معیار میدانی قابل دفاع برای Core Web Vitals در سال ۲۰۲۶ همچنان LCP حداکثر ۲٫۵ ثانیه، INP حداکثر ۲۰۰ میلی‌ثانیه و CLS حداکثر ۰٫۱ در صدک ۷۵ است [3]. مقادیر بهتر مانند LCP=1.2 یا CLS=0.05 هدف داخلی‌اند، نه الزام رسمی و نه نتیجهٔ اثبات‌شدهٔ این sandbox.

**WooCommerce-native extension:** قالب‌های WooCommerce با `add_theme_support('woocommerce')` و مسیرهای override کنترل می‌شوند؛ منطق business تا حد امکان hook-based است. مستند رسمی WooCommerce دربارهٔ hookها و ریسک overrideهای ناسازگار هشدار می‌دهد [4]. هر override باید با نسخهٔ WooCommerce pin‌شده بازبینی شود.

**صف پیامک و idempotency:** کد production core، outbox، idempotency key، lock، retry و correlation ID دارد و در request اصلی provider را صدا نمی‌زند. ارسال واقعی عمداً تا نصب filter/provider تأییدشده fail-closed باقی می‌ماند.

## گیت‌های لازم پیش از Go-Live

1. نسخهٔ دقیق WordPress، WooCommerce، PHP، MariaDB، افزونه‌ها و imageها pin و checksum شوند.
2. در staging واقعی `wp core verify-checksums`، WPScan و آزمون نقش‌ها اجرا شود و high/critical باز باقی نماند.
3. مسیرهای خرید: shop، product، cart، checkout، coupon، موجودی، callback موفق/ناموفق و retry دوباره‌کاری تست شوند.
4. CSP نهایی پس از inventory افزونه‌ها بازبینی شود؛ مقدار فعلی برای سازگاری با اکوسیستم شامل inline allowance است و نباید به‌عنوان CSP سخت‌گیرانهٔ Level 3 معرفی شود.
5. Lighthouse و axe روی desktop/mobile، keyboard-only و screen reader اجرا و artifact خام نگهداری شود.
6. Core Web Vitals با دادهٔ واقعی field measurement در صدک ۷۵، جداگانه برای mobile و desktop، گزارش شود.
7. backup/restore و rollback با RTO/RPO مصوب اجرا و زمان‌سنجی شود؛ نتیجهٔ کاغذی جایگزین restore واقعی نیست.
8. تست race برای stock و idempotency سفارش، rate-limit توزیع‌شده، Redis، timeout provider و alerting اجرا شود.
9. architect، owner محصول و مسئول عملیات sign-off کنند و پس از آن گواهی Go-Live با hash نسخه صادر شود.

## بازتولید

```bash
cd refactored-octo-waddle-main
./tests/production_gate.sh
```

برای تولید manifest شواهد در CI:

```bash
CI=1 ./tests/production_gate.sh
sha256sum -c docs/verification-evidence/sha256-manifest-ci.txt
```

## منابع رسمی

[1]: https://developer.wordpress.org/themes/global-settings-and-styles/ "WordPress: Global Settings and Styles (theme.json)"

[2]: https://www.w3.org/TR/WCAG22/ "W3C: Web Content Accessibility Guidelines (WCAG) 2.2"

[3]: https://web.dev/articles/vitals "web.dev: Web Vitals and 75th-percentile thresholds"

[4]: https://developer.woocommerce.com/docs/theming/theme-development/template-structure/ "WooCommerce: Template structure and overriding templates"

## تحویل

این گزارش همراه با کل پروژه، Theme، افزونه‌ها، اسکریپت‌های setup، تست‌ها، previewها و evidenceهای قبلی در آرشیو خروجی ارائه می‌شود. هر سند قبلی که وضعیت را NO-GO-LIVE اعلام کرده است معتبر باقی می‌ماند و این گزارش فقط روش و شواهد قابل تکرار را منسجم می‌کند؛ هیچ PDF یا امضای موجود به‌تنهایی جایگزین sign-off محیط واقعی نیست.
