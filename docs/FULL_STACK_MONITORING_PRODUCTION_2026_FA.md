# مانیتورینگ Full-Stack بامرو — Production Readiness Development

## نتیجه

برای پروژه دو endpoint عملیاتی اضافه شد:

```text
GET /wp-json/bamero/v1/health/live
GET /wp-json/bamero/v1/health/ready
```

`live` فقط زنده‌بودن WordPress و نسخه هسته را گزارش می‌کند. `ready` fail-closed است و تنها در صورت عبور همه dependency checks کد وضعیت ۲۰۰ برمی‌گرداند؛ در غیر این صورت ۵۰۳ می‌دهد.

## کنترل‌های readiness

endpoint آماده‌بودن موارد زیر را بررسی می‌کند:

| Check | معیار |
|---|---|
| WordPress | وجود runtime environment API |
| Database | پاسخ واقعی `SELECT 1` |
| WooCommerce | کلاس WooCommerce بارگذاری شده باشد |
| Outbox table | جدول notification outbox واقعاً وجود داشته باشد |
| SMS contract | provider filter ثبت شده باشد |
| Zarinpal configuration | merchant identifier از environment موجود باشد |

هیچ secret، API key یا credential در response برگردانده نمی‌شود؛ فقط Boolean readiness signal ارسال می‌شود.

## قرارداد مانیتور

برای load balancer یا orchestrator:

- `live` برای liveness probe استفاده شود.
- `ready` برای traffic admission استفاده شود.
- پاسخ ۵۰۳ از `ready` باید سرویس را از دریافت traffic جدید خارج کند.
- `ready` را برای liveness استفاده نکنید؛ خرابی موقت database نباید باعث restart بی‌مورد process شود.

## اجرای runtime بدون mock

اسکریپت زیر فقط به endpoint واقعی staging یا production وصل می‌شود و هیچ response یا serviceای را mock نمی‌کند:

```bash
./tests/monitor_runtime.sh https://staging.example.com
```

این دستور انتظار دارد هر دو endpoint ۲۰۰ بدهند و در غیر این صورت exit code غیرصفر می‌دهد. در محیط فعلی چون WordPress runtime و URL staging در دسترس نیست، اجرای HTTP واقعی ادعا نشده است.

## پایش full-stack پیشنهادی

در production، این سیگنال‌ها باید کنار health endpoints جمع‌آوری شوند:

- نرخ 5xx و latency برای page، REST و checkout؛
- نسبت successful payment verify به total callback؛
- تعداد outboxهای pending، failed و retry؛
- نرخ موفقیت ارسال SMS.ir؛
- DB connection errors و slow query rate؛
- queue age برای قدیمی‌ترین notification؛
- p75 و p95 برای LCP، INP و CLS؛
- backup freshness و restore verification؛
- error budget و SLO burn rate.

برای SLO برابر ۹۹٫۹٪:

\[
ErrorBudget = 1-0.999=0.001
\]

هشدار باید روی burn rate و خطای واقعی سرویس تنظیم شود، نه صرفاً روی process uptime.

## تغییر مهم مرتبط با production safety

در ممیزی monitoring مشخص شد `update_option('bamero_production_core_health', 1, false)` در زمان load افزونه اجرا می‌شد. این write بدون request یا schema gate حذف شد. health endpoint اکنون read-only است و هیچ database write هنگام load یا probe انجام نمی‌دهد.

## Static verification

```text
PASS: monitoring static checks; no runtime claim made
PHP lint: PASS for 21 files
Production gate: PASS — code-level gates only
```

این تفکیک intentional است: static implementation اثبات شده، اما صحت runtime باید با URL واقعی staging و credentials واقعی بررسی شود.

## References

[1]: https://developer.wordpress.org/reference/classes/wp_site_health/ "WP_Site_Health — WordPress Developer Resources"
[2]: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/ "Adding Custom Endpoints — WordPress REST API Handbook"
[3]: https://sre.google/workbook/implementing-slos/ "Implementing SLOs — Google SRE Workbook"
[4]: https://sre.google/workbook/alerting-on-slos/ "Alerting on SLOs — Google SRE Workbook"
