# فروشگاه اینترنتی بامرو

## توضیحات

فروشگاه اینترنتی **بامرو** یک پروژه **WordPress + WooCommerce** برای فروش رنگ ساختمانی، پوشش‌ها، زیرسازی، عایق/محافظ، ابزار و محصولات مرتبط در بازار ایران است. این بسته شامل Theme سفارشی، افزونه‌های هسته، کنترل‌های امنیتی، آداپتور SMS.ir و درگاه زرین‌پال و گیت‌های قابل‌تکرار کد است. وضعیت رسمی تحویل در `docs/PRODUCTION_READINESS_FINAL_2026_FA.md` ثبت شده است: **Release Candidate آمادهٔ ورود به staging؛ Go-Live مشروط به تست runtime و جایگذاری secretها**.

## ویژگی‌ها

### ✅ ویژگی‌های کلی
- **پشتیبانی کامل از RTL و زبان فارسی** (fa-IR).
- **طراحی حرفه‌ای و مدرن** با تم آبی، سفید و طلایی.
- **واکنش‌گرا** (Responsive) برای تمام دستگاه‌ها (موبایل، تبلت، دسکتاپ).
- **بهینه‌سازی شده برای موتورهای جستجو** (SEO).
- **امنیتی بالا** با تنظیمات کامل.
- **عملکرد بهینه** با کش، فشرده‌سازی و CDN.

### ✅ ویژگی‌های WooCommerce
- **دسته‌بندی‌ها و برچسب‌های محصولات** برای سازماندهی بهتر.
- **فیلترهای پیشرفته** (بر اساس قیمت، رنگ، برند، دسته‌بندی).
- **سبد خرید و تسویه حساب** ساده و کاربرپسند.
- **آداپتور رسمی زرین‌پال** برای request، callback و verify؛ فعال‌سازی با environment.
- **روش‌های ارسال** (پست، تیپاکس، تحویل در محل).
- **لیست علاقه‌مندی** با پلاگین YITH WooCommerce Wishlist.

### ✅ ویژگی‌های امنیتی
- **کلیدهای امنیتی** برای محافظت در برابر حملات.
- **محروم کردن دسترسی** به فایل‌های حساس (wp-config.php, xmlrpc.php).
- **غیرفعال کردن ویرایش فایل‌ها** از پنل مدیریت.
- **غیرفعال کردن XML-RPC** برای جلوگیری از حملات Brute Force.
- **مخفی کردن ورژن WordPress** برای کاهش خطرات امنیتی.

### ✅ ویژگی‌های عملکرد
- **کش سرور** با WP Super Cache.
- **فشرده‌سازی Gzip** برای کاهش حجم فایل‌ها.
- **Lazy Load** برای تصاویر.
- **Minify و Concatenate** برای CSS و JavaScript.
- **Preload و Preconnect** برای فایل‌های حیاتی.

### ✅ ویژگی‌های SEO
- **Schema Markup** برای محصولات و صفحات.
- **Meta Tags** (Open Graph, Twitter Cards, Canonical URLs).
- **Sitemap** خودکار با Rank Math.
- **Alt Text** برای تمام تصاویر.

### ✅ ویژگی‌های دسترسی‌پذیری
- **کیبورد ناوبری** برای کاربران با نیازهای ویژه.
- **ARIA Labels** برای عناصر تعاملی.
- **Skip to Content Link** برای کاربران صفحه‌خوان.
- **کنتراست رنگ** مناسب برای خوانایی.

## ساختار پروژه

```
refactored-octo-waddle/
├── wp-config.php                  # تنظیمات پایه WordPress
├── .htaccess                      # قوانین سرور و امنیتی
├── robots.txt                     # تنظیمات ربات‌ها
├── setup-bamero.sh               # اسکریپت خودکار برای تنظیمات
├── README.md                      # مستندات پروژه
├── DECISIONS.md                   # ثبت تصمیمات
├── .gitignore                     # فایل‌های نادیده گرفته شده
└── wp-content/
    ├── themes/
    │   └── bamero/               # تم سفارشی بامرو
    │       ├── css/
    │       │   ├── variables.css  # متغیرهای CSS
    │       │   ├── style.css      # استایل‌های اصلی
    │       │   ├── woocommerce.css # استایل‌های WooCommerce
    │       │   └── woocommerce-rtl.css # استایل‌های RTL
    │       ├── js/
    │       │   └── main.js         # اسکریپت‌های اصلی
    │       ├── fonts/
    │       │   └── IRANSans.woff2  # فونت فارسی
    │       ├── images/             # تصاویر
    │       ├── header.php         # سربرگ
    │       ├── footer.php         # پاورقی
    │       ├── index.php          # صفحه اصلی
    │       ├── functions.php      # توابع تم
    │       └── woocommerce.php    # تنظیمات WooCommerce
    └── plugins/
        ├── bamero-custom-plugin/ # پلاگین سفارشی
        │   └── bamero-custom-plugin.php
        └── bamero-woocommerce-setup/ # تنظیمات WooCommerce
            └── bamero-woocommerce-setup.php
```

## نصب و راه‌اندازی

### 1. دانلود و آپلود فایل‌ها
```bash
git clone https://github.com/mojirt37/refactored-octo-waddle.git
cd refactored-octo-waddle
```

### 2. اجرا کردن اسکریپت خودکار
```bash
chmod +x setup-bamero.sh
./setup-bamero.sh
```

این اسکریپت تمام فایل‌های پایه را ایجاد می‌کند و تنظیمات امنیتی را اعمال می‌کند.

### 3. تنظیمات پایگاه داده
- فایل `wp-config.php` را باز کنید.
- اطلاعات پایگاه داده را وارد کنید:
  ```php
  define('DB_NAME', 'نام_پایگاه_داده');
  define('DB_USER', 'نام_کاربر');
  define('DB_PASSWORD', 'رمز_عبور');
  define('DB_HOST', 'localhost');
  ```

### 4. نصب WordPress
- به آدرس `https://domain.com/wp-admin/install.php` بروید.
- مراحل نصب WordPress را انجام دهید.

### 5. فعال‌سازی تم و پلاگین‌ها
- به پنل مدیریت WordPress بروید.
- تم **بامرو** را فعال کنید.
- پلاگین‌های ضروری را نصب و فعال کنید:
  - WooCommerce
  - YITH WooCommerce Wishlist
  - `bamero-zarinpal-gateway` از همین بسته
  - Autoptimize
  - WP Super Cache
  - Rank Math SEO
  - Wordfence Security

### 6. پیکربندی نهایی
- **Permalinks**: به **Post Name** تغییر دهید.
- **Site Address**: آدرس سایت را تنظیم کنید.
- **Timezone**: به **Tehran (UTC+3:30)** تغییر دهید.
- **درگاه پرداخت زرین‌پال و SMS.ir**: secretها و template IDها را از `.env.example` در secret manager محیط قرار دهید؛ سپس staging را اجرا و callback/verify و OTP را تست کنید.
- **روش‌های ارسال**: در WooCommerce تنظیم کنید.

## اسکریپت خودکار

اسکریپت `setup-bamero.sh` تمام تنظیمات زیر را به صورت خودکار انجام می‌دهد:
- ایجاد فایل `wp-config.php` با کلیدهای امنیتی جدید.
- ایجاد فایل `.htaccess` با قوانین امنیتی و عملکرد.
- ایجاد فایل `robots.txt`.
- دانلود فونت IRANSans.
- ایجاد فایل‌های CSS و JavaScript.
- ایجاد فایل‌های PHP تم (header, footer, index, functions).
- ایجاد فایل‌های WooCommerce.
- کامیت و پوش تمام تغییرات.

## تست و اعتبارسنجی

برای اجرای گیت‌های قابل‌تکرار سطح کد:
```bash
./tests/production_gate.sh
```
این دستور عمداً ادعای موفقیت WordPress runtime، پرداخت، SMS، Lighthouse یا restore را ایجاد نمی‌کند؛ این موارد باید در staging مجاز با شواهد immutable اجرا شوند.
پیش‌نیاز محلی این گیت، `PHP CLI` و `sha256sum` است؛ نبود PHP عمداً با exit code غیرصفر گزارش می‌شود.

برای محاسبه حد پایین اطمینان، اندازه نمونه zero-failure، بودجه خطا و بررسی صدک‌های Core Web Vitals از داده واقعی staging:
```bash
./tests/quantitative_readiness.py --root . --out docs/verification-evidence/quantitative-readiness.json
```
این ابزار داده ساختگی تولید نمی‌کند و در نبود CSV runtime، صریحاً وضعیت را `INSUFFICIENT DATA` نگه می‌دارد. جزئیات معادلات در `docs/QUANTITATIVE_PRODUCTION_PROOF_2026_FA.md` است.

### تست عملکرد
- [GTmetrix](https://gtmetrix.com/)
- [PageSpeed Insights](https://pagespeed.web.dev/)
- [WebPageTest](https://www.webpagetest.org/)

### تست امنیتی
- [Wordfence](https://www.wordfence.com/)
- [Sucuri](https://sucuri.net/)

### تست SEO
- [Google Search Console](https://search.google.com/search-console)
- [Rank Math](https://rankmath.com/)

### تست دسترسی‌پذیری
- [WAVE](https://wave.webaim.org/)
- [axe](https://www.deque.com/axe/)

## مستندسازی

برای اطلاعات بیشتر، به فایل‌های زیر مراجعه کنید:
- [DECISIONS.md](DECISIONS.md) - ثبت تصمیمات پروژه
- [گزارش نهایی آمادگی استقرار ۲۰۲۶](docs/PRODUCTION_READINESS_FINAL_2026_FA.md) - شواهد، منابع و گیت‌های Go-Live
- [گزارش اثبات کمی Production ۲۰۲۶](docs/QUANTITATIVE_PRODUCTION_PROOF_2026_FA.md) - معادلات، confidence bounds، SLO و محدودیت شواهد
- [گزارش ممیزی playbook ارشد](docs/PLAYBOOK_COMPLIANCE_AUDIT_2026_FA.md) - تطبیق تکنیک‌ها و شکاف‌های runtime
- [wp-content/themes/bamero/README.md](wp-content/themes/bamero/README.md) - مستندات تم

## مشارکت

برای مشارکت در پروژه، لطفاً یک Pull Request ارسال کنید.

## مجوز

این پروژه تحت مجوز **MIT** قرار دارد.
