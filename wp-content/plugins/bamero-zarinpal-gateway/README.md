# Bamero Zarinpal Gateway

این افزونه آداپتور رسمی v4 زرین‌پال را با `request`، انتقال کاربر، `callback` و `verify` پیاده می‌کند. Merchant ID و base URL فقط از environment خوانده می‌شوند. قبل از فعال‌سازی درگاه، واحد پول، callback عمومی HTTPS و مبلغ واقعی باید در staging با sandbox/پذیرنده تأیید شود.

افزونه برای پاسخ `100` موفق و `101` تراکنش قبلاً verify‌شده رفتار idempotent دارد. هیچ email یا secret در دیتابیس یا کد ذخیره نمی‌شود.
