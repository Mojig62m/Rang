# گزارش نهایی آمادگی استقرار بامرو — ۲۱ سپتامبر ۲۰۲۶

## نتیجه اجرایی

این بسته در سطح **Release Candidate آماده ورود به staging و استقرار کنترل‌شده** است. گیت‌های کد، syntax، امنیت ایستا، رابط کاربری، RTL، email-free و قراردادهای اتصال SMS.ir و زرین‌پال با موفقیت اجرا شده‌اند. با این حال، **Go-Live عمومی هنوز مشروط است**؛ زیرا در این محیط به WordPress/WooCommerce اجراشده، دیتابیس واقعی، دامنه HTTPS، کلید SMS.ir، template IDها، Merchant ID زرین‌پال، Redis و ابزارهای تست خارجی دسترسی وجود ندارد. صدور گواهی قطعی در این وضعیت خلاف ممیزی قابل اتکا است.

## تغییرات انجام‌شده در این نوبت

درگاه `bamero-zarinpal-gateway` اضافه شد. این افزونه چرخه رسمی زرین‌پال را اجرا می‌کند: درخواست پرداخت، انتقال کاربر، دریافت `Authority` و `Status`، بررسی مالکیت سفارش، فراخوانی `verify` و تکمیل idempotent برای پاسخ‌های `100` و `101`. Merchant ID و URL API فقط از environment خوانده می‌شوند و در دیتابیس یا کد hard-code نشده‌اند.

آداپتور رسمی SMS.ir نیز در `bamero-production-core` اضافه شد. آداپتور از endpoint `v1/send/verify`، header `X-API-KEY` و template IDهای مستقل برای OTP و وضعیت سفارش استفاده می‌کند. صف outbox، retry، قفل پردازش و idempotency قبل از ارسال برقرار می‌ماند. اگر secret یا template ID تنظیم نشده باشد، سیستم fail-closed است و پیامک جعلی یا موفقیت ساختگی تولید نمی‌کند.

قالب نیز با focus قابل مشاهده، حداقل touch target عملی ۴۴ پیکسل، aspect ratio تصاویر محصول، container محدود، `prefers-reduced-motion` و قواعد responsive تکمیل شد. سایت همچنان RTL و mobile-first است و فیلد یا حمل‌ونقل ایمیل برای مشتری ندارد؛ ایمیل فنی داخلی WordPress فقط برای محدودیت schema ایجاد می‌شود و برای ارتباط، login یا اعلان استفاده نمی‌شود.

## شواهد قابل بازتولید

| کنترل | نتیجه | روش یا فایل شاهد |
|---|---:|---|
| PHP 8.3 syntax lint | PASS | `find ... -name '*.php' ... php -l` |
| production gate کدی | PASS | `tests/production_gate.sh` |
| theme JSON | PASS | `JSON_THROW_ON_ERROR` |
| الگوهای secret رایج | PASS | جستجوی کلیدهای رایج و private key |
| static security و WooCommerce template boundaries | PASS | `tests/static_checks.sh` |
| email-free boundary | PASS | `tests/email_free_static_checks.sh` |
| UI/UX، RTL و reduced motion | PASS | `tests/ui_ux_static_checks.sh` |
| درگاه زرین‌پال | PASS در سطح syntax/static contract | `wp-content/plugins/bamero-zarinpal-gateway/` |
| SMS.ir | PASS در سطح syntax/static contract | `bamero_sms_ir_provider_send` |

خروجی release gate اعلام می‌کند: `RELEASE GATE: PASS — code-level gates only`. این عبارت عمداً به معنی موفقیت تست واقعی پرداخت، ارسال پیامک یا restore نیست.

## قراردادهای secret و API

مقادیر زیر باید در secret manager یا environment سرویس قرار گیرند و هرگز commit نشوند:

```dotenv
SMS_PROVIDER=sms_ir
SMS_IR_API_BASE_URL=https://api.sms.ir/v1
SMS_IR_API_KEY=
SMS_IR_TEMPLATE_LOGIN_OTP=
SMS_IR_TEMPLATE_ORDER_PROCESSING=
SMS_IR_TEMPLATE_ORDER_DELIVERED=
SMS_IR_TEMPLATE_ORDER_CANCELLED=
SMS_IR_TEMPLATE_PAYMENT_FAILED=
SMS_IR_TEMPLATE_REFUND_COMPLETED=

ZARINPAL_API_BASE_URL=https://payment.zarinpal.com/pg/v4
ZARINPAL_MERCHANT_ID=
ZARINPAL_CURRENCY=IRT
```

در هر template پیامک، نام پارامتر باید با `SMS_IR_OTP_PARAMETER` و `SMS_IR_ORDER_PARAMETER` منطبق باشد. برای زرین‌پال، واحد مبلغ باید با واحد ثبت‌شده در پذیرنده و تنظیمات WooCommerce تطبیق داده شود. پس از تنظیم credential، افزونه محلی `bamero-zarinpal-gateway/bamero-zarinpal-gateway.php` در WordPress فعال و درگاه از صفحه پرداخت بررسی شود.

## معیارهای پذیرش staging قبل از Go-Live

۱. WordPress، WooCommerce، PHP، MariaDB/MySQL، افزونه‌ها و imageها با نسخه دقیق pin شوند و از منبع رسمی دریافت شوند. راهنمای رسمی WordPress به‌روزرسانی مستمر، حفاظت `wp-config.php`، غیرفعال‌سازی ویرایش فایل و پشتیبان‌گیری را توصیه می‌کند [1] [2].

۲. staging با HTTPS، cron واقعی یا worker مدیریت‌شده، object cache، backup و restore آزمایشی آماده شود. اجرای migration جدول outbox و فعال‌سازی افزونه‌ها باید با log قابل ارائه انجام شود.

۳. در SMS.ir با template واقعی، یک OTP، یک اعلان processing و یک اعلان completed ارسال شود. پاسخ HTTP، شناسه پیام، retry، timeout و وضعیت delivery ثبت و secretها redacted شوند. مستند SMS.ir برای OTP از `POST /v1/send/verify`، `X-API-KEY`، `templateId` و `parameters` استفاده می‌کند [3].

۴. در زرین‌پال ابتدا sandbox یا پذیرنده آزمایشی استفاده شود. یک پرداخت موفق، لغو، callback تکراری، `verify` تکراری، مبلغ نامطابق و timeout تست شود. طبق مستند رسمی، callback با `Status=OK` باید با `verify` نهایی شود؛ کد `100` موفق و `101` نشان‌دهنده verify قبلی است [4].

۵. با Playwright یا مرورگر واقعی، مسیرهای home، shop، single product، cart، checkout، ورود OTP، ثبت‌نام OTP، خطای OTP، پرداخت و بازگشت بررسی شوند. تست باید روی mobile و desktop انجام شود و focus keyboard، target size، پیام خطا و no-email flow پوشش داده شود. WCAG 2.2 معیار Focus Not Obscured، Accessible Authentication و Target Size Minimum را مشخص می‌کند [5].

۶. Lighthouse و RUM اجرا شوند. هدف Core Web Vitals در صدک ۷۵ برای LCP حداکثر ۲٫۵ ثانیه، INP حداکثر ۲۰۰ میلی‌ثانیه و CLS حداکثر ۰٫۱ است؛ این مقادیر باید با field data و نه فقط screenshot اثبات شوند [6].

۷. WPScan، dependency scan، CSP evaluator، تست همزمانی checkout و restore کامل اجرا شوند. تا ثبت این شواهد، برچسب صحیح انتشار `STAGING-READY / GO-LIVE-PENDING` است.

## حکم نهایی

**کد و بسته برای تحویل به staging و جایگذاری API آماده است.** پس از تکمیل هفت کنترل محیطی بالا و ثبت شواهد، پروژه می‌تواند با معیار قابل ممیزی به Go-Live ارتقا یابد. ادعای «دیپلوی نهایی اثبات‌شده» بدون آن شواهد قابل تأیید نیست.

## References

[1]: https://developer.wordpress.org/advanced-administration/security/hardening/ "Hardening WordPress — Advanced Administration Handbook"
[2]: https://developer.wordpress.org/advanced-administration/wordpress/wp-config/ "Editing wp-config.php — Advanced Administration Handbook"
[3]: https://sms.ir/rest-api/ "SMS.ir REST API"
[4]: https://www.zarinpal.com/docs/paymentGateway/connectToGateway "راهنمای اتصال به درگاه اینترنتی زرین‌پال"
[5]: https://www.w3.org/TR/WCAG22/ "Web Content Accessibility Guidelines (WCAG) 2.2"
[6]: https://web.dev/articles/vitals "Web Vitals — web.dev"
