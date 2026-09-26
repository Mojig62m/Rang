# گزارش تحویل نسخهٔ اصلاح‌شدهٔ بامرو

**تاریخ:** ۱۹ سپتامبر ۲۰۲۶

## وضعیت

نسخهٔ حاضر در سطح **کد اصلاح‌شده و آمادهٔ ورود به staging** تحویل می‌شود. این آرشیو بدون دسترسی به WordPress runtime، database واقعی، provider پیامک، درگاه پرداخت و سرور production نمی‌تواند گواهی Go-Live قطعی دریافت کند.

## اصلاحات انجام‌شده

- حذف قالب‌های اجرایی WooCommerce email.
- حذف مسیرهای PHP دارای transport ایمیل و جایگزینی فرم تماس/مشاوره با صف پیامک.
- مسدودسازی transport پلتفرم در سطح hook.
- استفاده از شناسهٔ داخلی غیرقابل‌مسیریابی برای سازگاری WordPress.
- اتصال OTP به transactional outbox به‌جای ارسال مستقیم در request.
- ایجاد جدول `wp_bamero_notification_outbox` با idempotency key، retry، lock و correlation ID.
- اضافه‌شدن worker زمان‌بندی‌شده برای پردازش صف پیامک.
- اضافه‌شدن رویدادهای پیامکی برای تغییر وضعیت سفارش.
- اضافه‌شدن rate limit و hash امن OTP در مسیر موجود احراز هویت.
- اضافه‌شدن tokenهای UI، focus-visible، کارت‌های tactile، gradient کنترل‌شده، responsive rules و پشتیبانی reduced-motion.
- حذف newsletter ایمیلی از footer و جایگزینی با CTA پشتیبانی موبایلی.
- اضافه‌شدن `.env.example` برای متغیرهای provider پیامک بدون secret واقعی.
- اضافه‌شدن تست static حذف ایمیل.

## تست‌های اجراشده

| آزمون | نتیجه |
|---|---|
| PHP 8.3 lint روی همه فایل‌های PHP | PASS |
| تست‌های static امنیتی موجود | PASS |
| تست‌های static UI/UX موجود | PASS |
| تست seed | PASS؛ ۱۰ محصول و ۱۰ کاربر synthetic |
| تست static email-free | PASS؛ هیچ transport ممنوع در PHP اجرایی پیدا نشد |
| حذف قالب‌های email | PASS |
| اعتبارسنجی JSON audit trail | PASS |
| تست WordPress/WooCommerce runtime | اجرا نشد؛ runtime در sandbox موجود نیست |
| تست provider پیامک و timeout | اجرا نشد؛ credential/provider واقعی موجود نیست |
| تست callback واقعی درگاه | اجرا نشد؛ staging و gateway موجود نیست |
| Lighthouse/axe روی صفحات واقعی | اجرا نشد؛ URL staging موجود نیست |
| backup/restore و rollback حین پرداخت | اجرا نشد؛ محیط production/staging مجاز موجود نیست |

## الزامات فعال‌سازی در staging

۱. مقادیر SMS provider، secret داخلی، database و payment gateway فقط از secret manager تزریق شوند.

۲. فیلتر `bamero_sms_provider_send` با provider تأییدشده پیاده‌سازی شود؛ در وضعیت فعلی نبود provider عمداً خطای قابل مشاهده می‌دهد و پیامک جعلی ارسال نمی‌کند.

۳. جدول outbox با activation یا migration اجرا شود.

۴. Gateهای ۲ تا ۱۲ از Action اصلی در staging واقعی اجرا و خروجی آن‌ها به این گزارش افزوده شود.

۵. پیش از Go-Live، نسخه‌های WordPress، WooCommerce، PHP، database، افزونه‌ها و imageها pin و ثبت شوند.

## محدودیت مهم

این بسته **ادعا نمی‌کند که پرداخت واقعی، ارسال پیامک واقعی، accessibility score حداقل ۹۵، restore کمتر از ۱۵ دقیقه یا rollback حین پرداخت اثبات شده است**؛ این موارد بدون محیط مجاز قابل آزمون نیستند.

## فایل‌های کلیدی

- `wp-content/plugins/bamero-production-core/bamero-production-core.php`
- `wp-content/plugins/bamero-mobile-auth/bamero-mobile-auth.php`
- `wp-content/themes/bamero/style.css`
- `wp-content/themes/bamero/css/variables.css`
- `tests/email_free_static_checks.sh`
- `.env.example`
- `docs/email-free-implementation.md`
- `docs/deployment-runbook.md`

## نتیجه

کد و کنترل‌های قابل اجرای محلی اصلاح و lint شده‌اند. نسخه برای ورود به staging آماده است، اما تا اجرای Gateهای runtime و دریافت sign-off معتبر، وضعیت آن **NO-GO-LIVE** باقی می‌ماند.
