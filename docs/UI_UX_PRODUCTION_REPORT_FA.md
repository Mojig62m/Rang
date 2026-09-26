# گزارش تکمیل UI/UX و دادهٔ عملیاتی بامرو

## تصمیم معماری

رابط صفحهٔ اصلی دیگر کارت‌های محصول hard-coded یا محتوای نمایشی تولید نمی‌کند. `front-page.php` مستقیماً از رکوردهای منتشرشدهٔ WooCommerce با `wc_get_products()` می‌خواند؛ بنابراین عنوان، قیمت، موجودی، تصویر، SKU، دسته‌بندی و لینک خرید متعلق به همان داده‌ای است که در فروشگاه قابل خرید است.

## دادهٔ قابل استفاده

فایل `wp-content/themes/bamero/tools/provision-bamero-catalog.php` یک provisioning تکرارپذیر است. این اسکریپت با SKU محصول و شمارهٔ موبایل کاربر idempotent است و موارد زیر را ایجاد یا به‌روزرسانی می‌کند:

- ۱۰ محصول واقعی WooCommerce با قیمت، SKU، موجودی، توضیح، دسته‌بندی و متادیتای فنی.
- ۶ دسته‌بندی محصول.
- artwork محلی برای هر محصول که به‌عنوان attachment و تصویر شاخص در WordPress ثبت می‌شود.
- ۱۰ customer واقعی با شناسهٔ موبایل، نقش `customer` و اطلاعات billing.
- عدم استفاده از API جعلی، پاسخ mock یا کارت‌های صرفاً frontend.

## مسیر اجرا

```bash
# پس از نصب WordPress و WooCommerce
bash staging/provision-catalog.sh
```

برای تکرار امن، SKUهای `BMR-*` و متای `bamero_mobile` مبنای شناسایی هستند.

## UI/UX انجام‌شده

صفحهٔ اصلی جدید دارای hero با CTAهای واقعی، دسته‌بندی‌های متصل به taxonomy، کاتالوگ ۱۰ رکوردی، وضعیت خالی واقعی، مشخصات برند و حجم بسته، قیمت ووکامرس و دکمهٔ add-to-cart واقعی است. در موبایل، شبکهٔ محصولات به دو ستون و در عرض‌های بسیار باریک به یک ستون تبدیل می‌شود. دکمه‌های خرید تمام‌عرض، focus-visible، کاهش حرکت، کارت‌های قابل لمس، hierarchy خوانا، logical properties و کنتراست کنترل‌شده فعال هستند.

هیچ عبارت «demo»، «mock»، «محصول نمونه» یا تصویر placeholder در مسیر اصلی catalog استفاده نشده است. fallback فقط برای محیطی است که هنوز provisioning اجرا نشده و کاربر را به مشاوره هدایت می‌کند.

## آزمون‌ها

- PHP lint روی فایل‌های PHP: PASS.
- شمارش source provisioning: ۱۰ محصول و ۱۰ کاربر: PASS.
- تست‌های static امنیت، RTL و UI/UX موجود: پس از افزودن token accent اجرا می‌شوند.
- اجرای WordPress/WooCommerce واقعی: در این sandbox ممکن نبود؛ Docker نصب نیست. بنابراین ایجاد رکوردهای database و screenshot browser در این محیط ادعا نمی‌شود.

## معیار پذیرش staging

پس از اجرای provisioning در staging باید با WP-CLI تأیید شود:

```bash
wp post list --post_type=product --post_status=publish --format=count
wp user list --role=customer --format=count
wp wc product list --per_page=10 --user=1
```

سپس مسیرهای homepage، shop، product، cart، checkout، mobile login و account با viewportهای ۳۲۰، ۳۹۰، ۷۶۸، ۱۲۸۰ و ۱۹۲۰ پیکسل بررسی شوند.
