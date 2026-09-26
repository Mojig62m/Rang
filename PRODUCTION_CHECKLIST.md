# چک‌لیست پروداکشن – فروشگاه بامرو

## اصلاحات انجام‌شده در این نسخه

- [x] مسیر preload اشتباه `css/style.css` در header اصلاح شد.
- [x] تعریف‌های `DISALLOW_FILE_*` از functions.php حذف و فقط در wp-config باقی ماند.
- [x] `remove_action` نامعتبر از wp-config حذف شد.
- [x] بارگذاری فونت IRANSans و RTL ووکامرس بهبود یافت.
- [x] فیلتر مخرب که عنوان منوها را خراب می‌کرد حذف شد.
- [x] کلاس `rtl` روی body و header تقویت شد.
- [x] `WPLANG` و `FORCE_SSL_ADMIN` اضافه شد.

## کارهای باقی‌مانده قبل از دیپلوی واقعی

### ۱. دارایی‌ها (Assets)
- فایل‌های `images/logo.png` و `images/favicon.png` را اضافه کنید (الان فقط placeholder.svg وجود دارد).
- برای حالت آفلاین/پایدارتر: فونت IRANSans را در پوشه `fonts/` قرار دهید.

### ۲. امنیت و کلیدها
- کلیدهای AUTH_KEY و ... را از https://api.wordpress.org/secret-key/1.1/salt/ تولید و جایگزین کنید.
- رمز عبور دیتابیس قوی تنظیم کنید.
- SSL کامل (نه فقط admin) روی هاست فعال باشد.

### ۳. تنظیمات وردپرس/ووکامرس
- زبان سایت را روی **فارسی** قرار دهید (Settings → General).
- Permalinks → Post name
- منطقه زمانی: تهران
- درگاه زرین‌پال را دستی نصب و پیکربندی کنید.
- پلاگین‌های ضروری: WooCommerce، Rank Math، WP Super Cache / Autoptimize، Wordfence

### ۴. تست نهایی
- موبایل (Chrome DevTools + دستگاه واقعی)
- RTL کامل صفحات فروشگاه، سبد، تسویه
- سرعت (PageSpeed / GTmetrix)
- فرم مشاوره رنگ و ایمیل
- پرداخت تست زرین‌پال

### ۵. دیپلوی
- از اسکریپت `setup-bamero.sh` یا docker-compose برای استیجینگ استفاده کنید.
- بعد از آپلود، `wp rewrite flush` و پاک‌سازی کش انجام دهید.
