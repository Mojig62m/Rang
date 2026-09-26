# ADR-0001: معماری فروشگاه

## تصمیم
تم کلاسیک فعلی حفظ می‌شود و منطق کسب‌وکار در افزونه `bamero-production-core` قرار می‌گیرد؛ قالب فقط از هوک‌ها و APIهای WooCommerce استفاده می‌کند.

## دلیل
کاهش coupling، امکان تعویض تم، سازگاری با WooCommerce 9.x و رعایت invariant عدم تغییر هسته WordPress.

## پیامد
به‌روزرسانی‌های WooCommerce باید با تست قالب‌ها و قرارداد `BAMERO_CART_SELECTOR` همراه باشد.

# ADR-0002: کش

نتایج پرهزینه در transient با TTL یک ساعت نگهداری می‌شود و در `save_post_product`/حذف محصول invalidate می‌شود. Object cache واقعی Redis باید توسط میزبان فعال شود؛ transient بدون persistent object cache فقط fallback است.

# ADR-0003: انتخاب افزونه

افزونه‌ها فقط از WordPress.org یا vendor رسمی، با نگهداری فعال و بدون CVE حل‌نشده high/critical انتخاب می‌شوند. درگاه زرین‌پال عمداً خودکار نصب نمی‌شود و باید از منبع رسمی و پس از بررسی نسخه نصب شود.

# ADR-0004: استقرار

TLS termination، PHP 8.3، MySQL سازگار با WordPress، Redis، secret injection محیطی و WAF در لایه میزبان توصیه می‌شود. `wp-config.php` فقط env را مصرف می‌کند و نصب/ویرایش افزونه در production بسته است.
