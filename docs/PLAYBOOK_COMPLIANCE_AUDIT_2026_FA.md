# ممیزی playbook ارشد WordPress/WooCommerce — ۲۱ سپتامبر ۲۰۲۶

## نتیجه بدون ادعا

این ممیزی نشان می‌دهد پروژه از نظر **ساختار کد، قالب، کنترل‌های امنیتی، RTL، responsive UI، صف پیامک، آداپتور پرداخت، HPOS declaration و release gate ایستا** وضعیت خوبی دارد. اما «آمادگی کد» با «اثبات Go-Live» یکی نیست. در این sandbox، اجرای واقعی WordPress/WooCommerce، دیتابیس production-like، credentials سرویس‌ها، browser E2E، restore، load test، RUM و rollback انجام نشده است. بنابراین نتیجه معتبر فعلی `CODE-READY / STAGING-READY / GO-LIVE-PENDING` است، نه گواهی Go-Live قطعی.

## ماتریس تطبیق

| حوزه playbook | وضعیت | شواهد یا اقدام |
|---|---|---|
| Design tokens و `theme.json` | انجام‌شده | `theme.json`، `variables.css`، spacing و رنگ‌های semantic |
| component CSS و specificity پایین | انجام‌شده | `:where()`، فایل `components-production.css` و dependency مستقل |
| container queries | انجام‌شده به‌صورت progressive enhancement | `@container bamero-products`؛ fallbackهای viewport حفظ شده‌اند |
| RTL و logical properties | انجام‌شده | تست UI/UX بدون physical spacing/float directions |
| Typography و WOFF2 | انجام‌شده | فونت محلی، `font-display: swap` و preload محدود |
| LCP، dimensions و CLS | تا حد کد انجام‌شده | aspect-ratio و ابعاد image؛ LCP واقعی هنوز با Lighthouse/RUM سنجیده نشده |
| JavaScript feature scope | جزئی | cart script روی سطوح Commerce محدود شده؛ `main.js` هنوز کاملاً feature-split نشده |
| Server-render first | عمدتاً انجام‌شده | PHP templates و progressive enhancement؛ checkout runtime باید در staging بررسی شود |
| Search state machine و analytics | ناقص | search پایه موجود است، اما suggestion/error/zero-result analytics و keyboard E2E اثبات نشده |
| Filter URL state | جزئی | فیلترهای query-based موجودند؛ canonicalization و cache cardinality تست نشده |
| Cart/Checkout رسمی | کد محافظه‌کارانه | templateها hookهای WooCommerce را استفاده می‌کنند؛ Cart/Checkout Blocks و Store API runtime تست نشده |
| جداسازی business logic از Theme | جزئی | منطق اصلی در plugins است، اما custom product meta و بخشی از presentation logic در Theme باقی مانده |
| CRUD و HPOS | بهبود داده شد | gateway از `WC_Order`/CRUD استفاده می‌کند و `FeaturesUtil::declare_compatibility` اضافه شده است. باید با HPOS واقعی تست شود [1] |
| N+1 و query budget | اثبات‌نشده | static SQL boundary هست؛ query profiling و `EXPLAIN` با دیتابیس واقعی اجرا نشده |
| cache strategy | بهبود داده شد | cart/checkout/account و کاربران logged-in با `DONOTCACHEPAGE` و `nocache_headers()` از cache عمومی جدا شدند؛ CDN واقعی تست نشده |
| object cache و invalidation | جزئی | transient invalidation برای catalog هست؛ Redis hit-rate، TTL policy و cache cardinality اثبات نشده |
| email-free policy | انجام‌شده در سطح کد | `wp_mail` و WooCommerce email hooks مسدود شده؛ customer-facing email UI نیز باید در staging scan شود |
| SMS adapter | انجام‌شده در سطح قرارداد | SMS.ir `send/verify`، `X-API-KEY`، template IDs و retry outbox؛ ارسال واقعی و delivery report انجام نشده [2] |
| Payment adapter | انجام‌شده در سطح قرارداد | Zarinpal request/callback/verify و پاسخ‌های 100/101؛ sandbox/merchant واقعی تست نشده [3] |
| webhook/callback | جزئی | callback authority/order lookup دارد؛ verify واقعی، replay و failure transition باید تست شود |
| order state machine | جزئی | status-to-SMS map و WooCommerce status hooks وجود دارد؛ transition matrix کامل و تست‌شده نیست |
| idempotency checkout | ناقص برای اثبات قطعی | metadata و outbox idempotency وجود دارد؛ race test و atomic uniqueness در checkout واقعی اجرا نشده |
| request ID و structured logs | انجام‌شده در کد | `X-Request-ID`، `request_id` و `correlation_id` اضافه شده؛ log shipping/retention/PII review محیطی است |
| rate limiting و OTP | انجام‌شده در کد | transient limits، hash OTP، TTL، attempts و lockout؛ distributed rate limit با Redis تست نشده |
| admin capability | عمدتاً انجام‌شده | admin checks و nonceها در formها؛ هر مسیر جدید باید capability review staging داشته باشد |
| REST contract | محدود | endpointهای custom عمومی در این بسته کم هستند؛ اگر اضافه شوند باید schema و `permission_callback` داشته باشند [4] |
| visual regression | baseline موجود | screenshotهای قبلی وجود دارد؛ اجرای مقایسه خودکار بعد از تغییرات فعلی انجام نشده |
| E2E purchase flow | انجام نشده | پروژه test runner Playwright یا wp-env ندارد. WooCommerce تست E2E را با Playwright و wp-env مستند می‌کند [5] |
| performance budget/CI | انجام نشده | release gate syntax/static است؛ budget برای JS/CSS/requests و Lighthouse CI باید در CI افزوده شود |
| RUM/Core Web Vitals | انجام نشده | LCP/INP/CLS field data در دسترس نیست؛ فقط target مستند شده است [6] |
| WPScan/dependency scan | انجام نشده در این محیط | نیازمند staging URL/token و inventory نسخه‌ها |
| backup/restore | انجام نشده | backup واقعی، restore زمان‌دار و checksum DB ثبت نشده |
| migration/rollback | جزئی | dbDelta برای outbox هست؛ rollback schema، cache invalidation و external payment state runbook واقعی لازم است |
| cron/worker | جزئی | WP-Cron worker با retry وجود دارد؛ cron واقعی production باید فعال و monitor شود. WordPress برای disable کردن WP-Cron اجرای cron خارجی را لازم می‌داند [7] |
| immutable assets | ناقص | version query بر اساس `BAMERO_VERSION` هست؛ content-hashed build assets وجود ندارد |
| staging parity | اثبات‌نشده | نسخه دقیق PHP/WP/WC/DB/server در این sandbox ثبت نشده |
| contract tests خارجی | انجام نشده | نیازمند sandbox/API test credentials برای SMS.ir و زرین‌پال |
| canary/rollback | انجام نشده | rollout واقعی و rollback rehearsal در اختیار نیست |
| static analysis PHPStan/Psalm | انجام نشده | فقط `php -l` اجرا شده؛ افزودن analyzer نیازمند dependency و stubs مناسب است |
| unit/integration tests | محدود | shell static tests موجودند؛ WordPress integration test واقعی وجود ندارد |

## اصلاحات انجام‌شده در این ممیزی

سه اصلاح عملی در بسته اعمال شد. اول، هدر `X-Request-ID` و structured log با request ID اضافه شد. دوم، صفحات cart، checkout، account و sessionهای کاربر از cache عمومی جدا شدند. سوم، سازگاری HPOS اعلام و component CSS با container-query و versioning مستقل اضافه شد. همچنین `tests/production_preflight.sh` ساخته شد تا وضعیت static و runtime را از یکدیگر جدا کند و نبود API واقعی را مخفی نکند.

## preflight و release gate

حالت static با دستور زیر قابل اجراست:

```bash
./tests/production_preflight.sh static
./tests/production_gate.sh
```

حالت runtime فقط روی staging production-like و پس از تزریق secretها مجاز است:

```bash
WP_ENVIRONMENT_TYPE=staging \
SMS_PROVIDER=sms_ir \
SMS_IR_API_KEY='...' \
SMS_IR_API_BASE_URL='https://api.sms.ir/v1' \
ZARINPAL_API_BASE_URL='https://payment.zarinpal.com/pg/v4' \
ZARINPAL_MERCHANT_ID='...' \
./tests/production_preflight.sh runtime
```

این دستور فقط وجود و شکل secretها را کنترل می‌کند و secret را چاپ نمی‌کند. موفقیت preflight به‌تنهایی به معنی موفقیت ارسال پیامک یا پرداخت نیست.

## حکم نهایی

**موارد قابل‌پیاده‌سازی و قابل‌اثبات در سطح کد انجام شده‌اند.** مواردی که به سرویس واقعی، browser، دیتابیس، شبکه یا عملیات production نیاز دارند عمداً به‌عنوان انجام‌شده گزارش نشده‌اند. برای صدور Go-Live واقعی باید ابتدا evidence bundle شامل E2E purchase، SMS delivery، Zarinpal verify، HPOS، Lighthouse/RUM، WPScan، restore و rollback rehearsal ایجاد شود. بدون این bundle، عبارت «Go-Live اثبات‌شده» از نظر فنی و ممیزی قابل دفاع نیست.

## پیشنهادهای اولویت‌دار

اولویت اول اجرای staging production-like با نسخه‌های pin‌شده و WordPress/WooCommerce واقعی است. اولویت دوم ایجاد Playwright smoke suite برای مسیر login OTP تا order paid و تست callback تکراری است. اولویت سوم اجرای Lighthouse CI و جمع‌آوری LCP، INP و CLS در field است. اولویت چهارم تست Redis، race در checkout و restore زمان‌دار است. اولویت پنجم اجرای PHPStan/Psalm، WPScan و dependency scan است. اولویت ششم rehearsal برای rollback کد، دیتابیس، cache و payment state است.

## References

[1]: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/ "HPOS extension recipe book — WooCommerce Developer Docs"
[2]: https://sms.ir/rest-api/ "SMS.ir REST API"
[3]: https://www.zarinpal.com/docs/paymentGateway/connectToGateway "راهنمای اتصال به درگاه اینترنتی زرین‌پال"
[4]: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/ "Adding Custom Endpoints — WordPress REST API Handbook"
[5]: https://developer.woocommerce.com/docs/contribution/testing/ "Testing — WooCommerce Developer Docs"
[6]: https://web.dev/articles/vitals "Web Vitals — web.dev"
[7]: https://developer.wordpress.org/advanced-administration/wordpress/wp-config/ "Editing wp-config.php — Alternative Cron and Cron Timeout"
