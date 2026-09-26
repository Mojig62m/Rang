# Runbook استقرار بامرو

## متغیرهای لازم
`DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`, هشت کلید WordPress (`AUTH_KEY` تا `NONCE_SALT`)، `WP_ENVIRONMENT_TYPE=production`, `DISALLOW_FILE_MODS=1`، و `BAMERO_PAYMENT_WEBHOOK_SECRET` باید از secret manager تزریق شوند. هیچ secret در Git یا webroot ذخیره نشود.

## ترتیب اجرا
1. نسخه دقیق WordPress 6.7.x و WooCommerce 9.x را pin کنید.
2. فایل‌ها را خارج از webroot آماده و سپس release اتمیک کنید.
3. افزونه هسته را فعال کنید: `wp plugin activate bamero-production-core`.
4. permalinkها را flush کنید و endpoint callback را با payload تستی HMAC بررسی کنید.
5. Redis را فعال کنید؛ hit rate هدف بالاتر از ۹۰٪ است.
6. تست‌های `tests/static_checks.sh` را اجرا کنید.
7. backup پایگاه داده و فایل‌ها را ثبت و سپس smoke test صفحات خانه، فروشگاه، محصول، سبد و checkout را انجام دهید.

## rollback
Release قبلی را فعال کنید، افزونه تازه را deactivate کنید، cacheها را purge کنید و سفارش‌های ایجادشده در بازه release را با گزارش callback تطبیق دهید. حذف داده انجام نشود.

## شاخص‌ها
conversion rate، revenue/hour، cart abandonment، SMS delivery rate و payment success rate باید با correlation ID لاگ شوند. هشدار anomaly پس از داشتن baseline حداقل هفت روزه و انحراف بیشتر از ۲σ فعال شود.
