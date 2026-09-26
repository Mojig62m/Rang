# گزارش ممیزی کامل Production بامرو — ۲۱ سپتامبر ۲۰۲۶

## حکم نهایی

ممیزی **۱۰۰٪ دامنه تعریف‌شده** اجرا شد. این عبارت به معنی بررسی تمام کنترل‌های ثبت‌شده در ممیز است، نه اثبات رفتار در همه حالت‌های ممکن جهان. چنین اثباتی برای یک سیستم نرم‌افزاری باز، با ورودی‌ها، شبکه‌ها، دستگاه‌ها، نسخه‌ها و سرویس‌های خارجی نامتناهی، از نظر ریاضی ممکن نیست.

ممیز به‌صورت **fail-closed** طراحی شده است. اگر یک کنترل حیاتی شکست بخورد یا evidence معتبر نداشته باشد، خروجی `NO-GO` می‌شود.

نتیجه واقعی اجرای ممیز:

```text
Audit scope coverage: 100%
Controls: 28
PASS: 16
FAIL: 0
BLOCKED: 12
Decision: NO-GO
```

دوازده کنترل runtime به دلیل نبود staging production-like، credentials واقعی، دیتابیس، مرورگر و شواهد عملیاتی `BLOCKED` هستند. بنابراین صدور گواهی Go-Live قطعی در این مرحله از نظر فنی و منطقی نادرست است.

## منطق تصمیم

هر کنترل با سه وضعیت ارزیابی می‌شود:

- `PASS`: شواهد قابل بازتولید و متناسب با کنترل وجود دارد.
- `FAIL`: شواهد موجود خلاف معیار است.
- `BLOCKED`: شواهد لازم برای نتیجه‌گیری وجود ندارد.

برای کنترل‌های blocking، تابع تصمیم به‌صورت زیر است:

\[
GoLive = \bigwedge_{i=1}^{m} (status_i = PASS)
\]

در این ممیزی:

\[
GoLive = PASS \land BLOCKED \land ... \land BLOCKED = FALSE
\]

پس نتیجه `NO-GO` است. نبود مشاهده با موفقیت یکسان نیست و ممیز اجازه تبدیل `BLOCKED` به `PASS` را نمی‌دهد.

## کنترل‌های موفق

۱۶ کنترل زیر با شواهد repository و اجرای واقعی اسکریپت‌ها PASS شدند. این کنترل‌ها شامل PHP lint برای ۲۱ فایل PHP، production gate، secret scan، تنظیمات سخت‌سازی WordPress، HTTPS admin، مسدودسازی فایل‌های حساس، email-free boundary، RTL و UI/UX static checks، قرارداد ایستای زرین‌پال، آداپتور SMS.ir، outbox رمز‌شده و durable، HPOS declaration، request/correlation ID، private-cache bypass، ابزار محاسبات کمی، container query و static preflight هستند.

این PASSها **اثبات کد و قرارداد ایستا** هستند. آن‌ها به‌تنهایی موفقیت HTTP، دیتابیس، payment gateway، SMS delivery یا restore را اثبات نمی‌کنند.

## کنترل‌های مسدودشده

۱۲ کنترل زیر تا زمانی که evidence در مسیر `docs/runtime-evidence/` قرار نگیرد، BLOCKED باقی می‌مانند:

| شناسه | کنترل حیاتی | دلیل BLOCKED |
|---|---|---|
| R01 | boot واقعی WordPress/WooCommerce | runtime واقعی در sandbox وجود ندارد |
| R02 | HPOS integration | دیتابیس و WooCommerce واقعی اجرا نشده‌اند |
| R03 | OTP و delivery پیامک | API key و template واقعی وجود ندارد |
| R04 | اعلان وضعیت سفارش | delivery evidence واقعی وجود ندارد |
| R05 | پرداخت زرین‌پال | merchant/sandbox و callback واقعی موجود نیست |
| R06 | Playwright revenue-path E2E | staging URL و test runner واقعی موجود نیست |
| R07 | Core Web Vitals p75 | داده واقعی browser/RUM موجود نیست |
| R08 | load، concurrency و race | محیط بار و دیتابیس مشابه production موجود نیست |
| R09 | backup و restore | backup checksum و restore run واقعی ثبت نشده |
| R10 | rollback و cache invalidation | rehearsal عملیاتی اجرا نشده |
| R11 | WPScan و dependency scan | target staging و inventory نهایی موجود نیست |
| R12 | cron/worker و retry | worker واقعی و delivery log موجود نیست |

## معیار پذیرش runtime

برای هر کنترل runtime باید یک evidence JSON دارای حداقل این مشخصات ثبت شود:

```json
{
  "control_id": "R05",
  "environment": "staging",
  "commit_sha": "immutable-commit",
  "started_at": "2026-09-21T...Z",
  "finished_at": "2026-09-21T...Z",
  "attempts": 4940,
  "failures": 0,
  "logs_sha256": "...",
  "operator": "...",
  "result": "PASS"
}
```

برای payment و SMS باید secretها redact شوند و شناسه تراکنش‌ها بدون افشای API key ذخیره شوند. برای backup و rollback باید checksum، زمان اجرا و نتیجه restore قابل بازبینی باشد.

## اثبات آماری مورد استفاده

برای یک SLO برابر ۹۹٫۹٪:

\[
ErrorBudget = 1 - 0.999 = 0.001
\]

در یک میلیون event، بودجه خطا برابر ۱٬۰۰۰ event است.

برای صفر شکست و اطمینان ۹۵٪ در یک کنترل:

\[
n \ge \left\lceil \frac{\ln(1-0.95)}{\ln(0.999)} \right\rceil = 2{,}995
\]

برای هفت کنترل با confidence خانوادگی ۹۵٪ و Bonferroni:

\[
n_i \ge \left\lceil \frac{\ln(0.05/7)}{\ln(0.999)} \right\rceil = 4{,}940
\]

این محاسبه فقط uncertainty binomial را کنترل می‌کند. استقلال آزمون‌ها، پوشش سناریوها، negative path، تغییرات دستگاه و شبکه باید جداگانه طراحی شوند. NIST نیز تست آماری نرم‌افزار را به مدل binomial، coverage و assumptions وابسته می‌داند [1].

برای Web Vitals، میانگین معیار پذیرش نیست. مقدار p75 داده واقعی کاربران باید بررسی شود:

\[
LCP_{p75} \le 2.5s,
\quad INP_{p75} \le 200ms,
\quad CLS_{p75} \le 0.1
\]

web.dev گزارش p75 و پرهیز از میانگین را توصیه می‌کند [2] [3].

## تغییرات نهایی انجام‌شده پیش از ممیزی

ضعف durability در outbox اصلاح شد. payload پیامک اکنون به‌صورت رمز‌شده و durable در outbox ذخیره می‌شود. OTP expiry برابر ۶۰۰ ثانیه است. اعلان‌های معمول سفارش یک روز عمر دارند. idempotency key یکتا باقی مانده و duplicate race بعد از insert نیز مدیریت می‌شود.

ممیز کامل و quantitative auditor نیز به repository اضافه شدند. اجرای ممیز کامل:

```bash
./tests/full_production_audit.py
```

این دستور در وضعیت فعلی عمداً با exit code غیرصفر پایان می‌یابد، چون `NO-GO` صحیح است. این شکست ابزار نیست؛ حفاظت در برابر صدور گواهی جعلی است.

## الزامات تبدیل NO-GO به GO

برای تبدیل تصمیم به `GO` باید هر ۱۲ فایل runtime evidence ایجاد شوند و ممیز دوباره اجرا شود. هر فایل باید از محیط staging واقعی، commit مشخص، timestamp، checksum log و نتیجه قابل بازبینی تولید شود. ساختن فایل خالی یا evidence ساختگی قابل قبول نیست و ممیز باید آن را رد کند.

## نتیجه بدون دام

پروژه از نظر کد و کنترل‌های repository در وضعیت خوبی قرار دارد و هیچ `FAIL` کدی در ممیزی کامل مشاهده نشد. بااین‌حال، **۱۰۰٪ پوشش ممیزی به معنی ۱۰۰٪ سلامت عملیاتی نیست**. در حال حاضر ۱۰۰٪ کنترل‌های تعریف‌شده بررسی شده‌اند، اما ۱۲ کنترل حیاتی هنوز observation واقعی ندارند. بنابراین معتبرترین و حرفه‌ای‌ترین حکم:

> **Production Candidate — Code Verified, Runtime Evidence Blocked, Final Go-Live Not Approved.**

## References

[1]: https://nvlpubs.nist.gov/nistpubs/Legacy/IR/nistir6129.pdf "NIST IR 6129 — Software Testing by Statistical Methods"
[2]: https://web.dev/articles/vitals-field-measurement-best-practices "Best practices for measuring Web Vitals in the field — web.dev"
[3]: https://web.dev/articles/vitals "Web Vitals — web.dev"
[4]: https://sre.google/workbook/implementing-slos/ "Implementing SLOs — Google SRE Workbook"
[5]: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/ "HPOS extension recipe book — WooCommerce Developer Docs"
