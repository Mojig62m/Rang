# گزارش اجرای سخت‌سازی UI/UX بامرو

**مرجع:** `BAMERO_UI_UX_HARDENING_V1`

**تاریخ اجرا:** ۱۷ سپتامبر ۲۰۲۶

**نتیجه:** `PARTIAL — LOCAL HARDENING PASS / STAGING CERTIFICATION BLOCKED`

## خلاصه

بخش‌های قابل‌اجرای hardening در repository انجام شد. فونت Vazirmatn رسمی به‌صورت محلی اضافه شد، منابع خارجی فونت و Font Awesome از enqueue حذف شدند، preload فونت فقط برای خانه و محصول اضافه شد، tokenهای CSS یکپارچه‌تر شدند، tokenهای legacy برای جلوگیری از undefined variable تعریف شدند، physical directionهای RTL حذف شدند، آیکون‌های محلی بدون CDN اضافه شدند، CTA موبایل با safe-area محدود شد و static gate اختصاصی UI/UX با موفقیت اجرا شد.

گیت‌های وابسته به Staging، دیتاشیت رسمی، نمونه فیزیکی رنگ و تست browser واقعی اجرا نشده‌اند؛ زیرا دسترسی staging، WordPress runtime، Playwright، Lighthouse و axe-core در محیط موجود نیست. بنابراین گواهی `PRODUCTION_UI_STABLE_CERTIFIED` صادر نشده است.

## تغییرات اجرایی

### Font pipeline

فایل رسمی Vazirmatn Variable WOFF2 از repository رسمی پروژه دریافت و در `wp-content/themes/bamero/assets/fonts/Vazirmatn-wght.woff2` ذخیره شد. مجوز OFL نیز کنار آن قرار گرفت. `@import` خارجی از `style.css` حذف شد و enqueue Google Fonts از `functions.php` حذف شد. `@font-face` محلی با `font-display: swap` و وزن variable در `variables.css` قرار گرفت.

برای صفحات خانه و محصول، اگر فایل فونت قابل‌خواندن باشد، preload با `as="font"` و `type="font/woff2"` تولید می‌شود. checksum فونت محلی در audit trail ذخیره شده است.

### Icon pipeline

enqueue مربوط به Font Awesome CDN حذف شد. stylesheet محلی `css/icons.css` ایجاد شد و آیکون‌های مورد استفاده فعلی با CSS maskهای SVG داخلی جایگزین شدند. آیکون‌ها دیگر به `cdnjs.cloudflare.com` وابسته نیستند. این مرحله وابستگی runtime را حذف می‌کند؛ audit کامل accessible name همه icon-only controls همچنان در staging لازم است.

### CSS tokens و RTL

tokenهای اصلی با namespace `--bamero-*` اضافه شدند و aliasهای سازگاری برای CSS قدیمی تعریف شدند. tokenهای مصرف‌شده مانند spacing، color، font، grid و shadow اکنون در token source تعریف دارند. یک static check هر variable مصرف‌شده را با تعریف‌های `variables.css` مقایسه می‌کند.

در `woocommerce-rtl.css`، `float: left/right` و `text-align: left/right` حذف شدند و به مقادیر logical یا alignment مبتنی بر start/end تبدیل شدند. تست ایستا برای physical spacing و alignment نیز اضافه شد.

### Mobile CTA

CTA افزودن به سبد در محصول موبایل اکنون با `env(safe-area-inset-bottom)` فاصله امن دارد. برای cart، checkout و حالت modal، fixed CTA غیرفعال یا static می‌شود. صفحه محصول نیز padding انتهایی دارد تا CTA روی محتوا قرار نگیرد.

## شواهد گیت‌های محلی

```text
Vazirmatn local WOFF2: PASS
OFL license file: PASS
Local icon stylesheet: PASS
No external font/icon CDN or CSS import: PASS
No physical float or spacing directions: PASS
No physical text alignment directions: PASS
font-display: swap: PASS
safe-area-inset-bottom: PASS
prefers-reduced-motion: PASS
CSS custom property definitions: PASS
PHP lint 8.3.6: PASS
Existing static checks: PASS
```

گزارش خام تست در `tests/ui-ux-static-checks.log` قرار دارد. harness آن در `tests/ui_ux_static_checks.sh` ذخیره شده است.

## گیت‌های مسدودشده

| گیت | وضعیت | دلیل |
|---|---|---|
| Lighthouse Performance ≥90 | BLOCKED | staging و Lighthouse CI موجود نیست |
| WebPageTest و FOUT <300ms | BLOCKED | URL عمومی و تست شبکه واقعی موجود نیست |
| Playwright پنج viewport | BLOCKED | WordPress runtime و Playwright project موجود نیست |
| axe-core و Accessibility ≥95 | BLOCKED | browser runtime و axe-core موجود نیست |
| keyboard flow واقعی | BLOCKED | صفحات runtime قابل‌دسترسی نیستند |
| 200% zoom و forced colors | BLOCKED | browser harness موجود نیست |
| gallery و ویدیوی واقعی | BLOCKED | assetهای مجاز و دیتاشیت/ویدیو مالک ارائه نشده‌اند |
| ΔE <3 | BLOCKED | نمونه فیزیکی RAL و spectrophotometer موجود نیست |
| smart cross-sell | PARTIAL | metadata فنی پایه وجود دارد؛ rule engine واقعی هنوز لازم است |
| Persian copy review | PARTIAL | بازبینی تخصصی زبان‌شناختی مالک محصول ثبت نشده است |

## نواقص باقی‌مانده با اولویت

اولویت P0، اجرای staging واقعی با WordPress و WooCommerce نسخه قفل‌شده و تست browser است. بدون آن هیچ ادعای پایداری production معتبر نیست.

اولویت P1، تبدیل جدول فنی موبایل به accordion واقعی، تکمیل label و error semantics فرم‌ها، افزودن تست contrast، حذف باقی‌مانده‌های `!important` و تعریف visual regression baseline است.

اولویت P2، افزودن gallery واقعی، تصویر سطح اجرا، ویدیوی زیرنویس‌دار، MSDS رسمی، ruleهای cross-sell مکمل و اندازه‌گیری ΔE با نمونه فیزیکی است.

## نتیجه

Hardening محلی با موفقیت انجام شد و gate ایستای جدید PASS است. با این حال، چون شواهد visual و runtime طبق دستور فایل اجباری‌اند و staging در دسترس نیست، وضعیت نهایی `PRODUCTION_UI_STABLE_CERTIFIED` نیست. وضعیت صحیح پروژه `LOCAL_HARDENING_PASS / STAGING_CERTIFICATION_BLOCKED` است.

## منبع فونت

[1]: https://github.com/rastikerdar/vazirmatn "Vazirmatn official repository and OFL-1.1 license"

[2]: https://www.w3.org/TR/WCAG22/ "Web Content Accessibility Guidelines 2.2"

[3]: https://web.dev/articles/font-best-practices "Font best practices for web performance"
