# گزارش کمی و قابل‌تکرار آمادگی Production بامرو — ۲۱ سپتامبر ۲۰۲۶

## نتیجه اصلی

این گزارش «اثبات کدی» را از «اثبات آماری runtime» جدا می‌کند. در سطح کد، release gate پس از اصلاحات جدید با exit code صفر اجرا شد. در سطح runtime، هیچ observation واقعی از staging، پرداخت، پیامک، مرورگر، بار، restore یا Core Web Vitals در این sandbox وجود ندارد. بنابراین نتیجه ریاضی قابل دفاع این است:

> **Code-level conformance: PASS. Runtime statistical proof: INSUFFICIENT DATA. Go-Live certificate: NOT ISSUED.**

این محدودیت نقص گزارش نیست؛ نتیجه مستقیم نظریه استنباط است. بدون نمونه مشاهده‌شده، نمی‌توان نرخ موفقیت، صدک ۷۵ یا قابلیت اطمینان سرویس را برآورد کرد.

## مدل SLI و SLO

برای هر مسیر قابل اندازه‌گیری، تعریف Google SRE استفاده می‌شود:

\[
SLI = \frac{good\ events}{total\ events}
\]

اگر هدف availability برابر ۹۹٫۹٪ باشد، بودجه خطا برابر است با:

\[
Error\ Budget = 1 - SLO = 1 - 0.999 = 0.001
\]

در یک میلیون رویداد، حداکثر خطای مجاز برابر است با:

\[
1{,}000{,}000 \times 0.001 = 1{,}000
\]

این بودجه باید برای مسیرهای جداگانه تعریف شود، زیرا موفقیت صفحه اصلی، OTP، پرداخت و ارسال سفارش یک SLI واحد نیستند.

## حد پایین اطمینان برای success rate

اگر از بین \(n\) اجرای مستقل، \(x\) اجرا موفق باشد، برآورد نقطه‌ای برابر است با:

\[
\hat p = \frac{x}{n}
\]

برای جلوگیری از ادعای بیش‌ازحد در نمونه‌های کوچک، حد پایین یک‌طرفه Wilson با \(z=1.95996\) برای اطمینان ۹۵٪ محاسبه می‌شود:

\[
L = \frac{\hat p + \frac{z^2}{2n} - z\sqrt{\frac{\hat p(1-\hat p)}{n}+\frac{z^2}{4n^2}}}{1+\frac{z^2}{n}}
\]

قبولی آماری یک gate با هدف ۹۹٫۹٪ فقط زمانی مجاز است که \(L \ge 0.999\) باشد. یک تست موفق یا حتی ۱۰۰ تست موفق این شرط را اثبات نمی‌کند.

## تعداد نمونه در حالت صفر خطا

برای \(n\) تست بدون خطا، احتمال مشاهده صفر شکست در حالی که قابلیت واقعی فقط \(p\) است برابر است با:

\[
P(0\ failure \mid p) = p^n
\]

برای رد این حالت با اطمینان \(c\)، شرط زیر لازم است:

\[
p^n \le 1-c
\]

پس:

\[
n \ge \left\lceil \frac{\ln(1-c)}{\ln(p)} \right\rceil
\]

با هدف reliability برابر ۹۹٫۹٪ و confidence برابر ۹۵٪:

\[
n = \left\lceil \frac{\ln(0.05)}{\ln(0.999)} \right\rceil = 2{,}995
\]

این عدد فقط برای **یک gate مشخص** و با فرض مدل binomial و پوشش مناسب است. NIST هشدار می‌دهد که استقلال آزمون‌ها، constancy احتمال موفقیت و coverage عملکرد باید بررسی شوند؛ تست‌های تکراری یک مسیر واحد جایگزین پوشش عملکردی نمی‌شوند [1].

## تصحیح چندگانه برای هفت gate حساس

اگر هفت gate مهم را هم‌زمان بررسی کنیم و بخواهیم confidence خانوادگی حداقل ۹۵٪ باقی بماند، Bonferroni مقدار خطای هر gate را محدود می‌کند:

\[
\alpha_i = \frac{0.05}{7} = 0.007142857
\]

\[
c_i = 1-\alpha_i = 0.992857143
\]

برای zero-failure proof در هر gate:

\[
n_i = \left\lceil \frac{\ln(0.007142857)}{\ln(0.999)} \right\rceil = 4{,}940
\]

بنابراین برای ادعای بسیار قوی «هفت gate با reliability حداقل ۹۹٫۹٪ و confidence خانوادگی ۹۵٪»، حداقل **۴٬۹۴۰ اجرای مستقل بدون شکست برای هر gate** لازم است. این عدد جایگزین تست طراحی‌شده، mutation، negative path و coverage نیست؛ فقط حد اطمینان آماری برای بخش binomial است.

## Core Web Vitals

برای LCP، INP و CLS از میانگین استفاده نمی‌شود. مقدار گزارش‌شده باید صدک ۷۵ توزیع واقعی کاربران باشد. معیار پذیرش پیشنهادی:

\[
LCP_{p75} \le 2.5s
\]

\[
INP_{p75} \le 200ms
\]

\[
CLS_{p75} \le 0.1
\]

محاسبه صدک در ابزار پروژه با روش nearest-rank انجام می‌شود:

\[
P_{75}=x_{\lceil 0.75n\rceil}
\]

پس تا زمانی که داده واقعی browser/RUM وجود ندارد، مقدار p75 «نامشخص» است، نه صفر و نه PASS. web.dev نیز گزارش percentile و پرهیز از میانگین را توصیه می‌کند [2] [3].

## آنچه واقعاً در پروژه اندازه‌گیری شد

| شاخص | مقدار مشاهده‌شده | نتیجه |
|---|---:|---|
| خروجی production gate کدی | exit code 0 | PASS |
| فایل PHP بررسی‌شده | ۲۱ | scope کد مشخص است |
| فایل CSS بررسی‌شده | ۶ | scope CSS مشخص است |
| secret pattern رایج | ۰ مورد | PASS |
| runtime HTTP observations | ۰ | فاقد شواهد |
| تراکنش واقعی SMS.ir | ۰ | فاقد شواهد |
| تراکنش واقعی زرین‌پال | ۰ | فاقد شواهد |
| نمونه واقعی LCP/INP/CLS | ۰ | فاقد شواهد |
| restore واقعی | ۰ | فاقد شواهد |
| اجرای Playwright E2E | ۰ | فاقد شواهد |

چهار ردیف اول evidence کدی هستند و چهار ردیف آخر عمداً PASS گزارش نشده‌اند.

## اصلاح reliability مهم در این نوبت

در ممیزی مشخص شد payload پیامک فقط در transient نگهداری می‌شد. transient برای outbox durable، restore یا multi-worker guarantee کافی نیست. اصلاح انجام‌شده شامل ذخیره `payload_ciphertext` در جدول outbox، رمزگذاری AES-256-GCM با کلید مشتق‌شده از `AUTH_KEY`، انقضای payload OTP پس از ۶۰۰ ثانیه، انقضای اعلان‌های عادی پس از یک روز، و مدیریت duplicate پس از unique-key race است. جدول همچنان `UNIQUE KEY idem` دارد. نتیجه این اصلاح، کاهش ریسک از دست‌رفتن payload در cache eviction یا restart است؛ با این حال، اثبات آن نیازمند integration test واقعی با دیتابیس و worker است.

## قانون تصمیم Go-Live

Go-Live فقط در صورت برقرار بودن همه شروط زیر مجاز است:

\[
GoLive = C \land R \land P \land E \land V \land B \land K
\]

که در آن:

- \(C\): code gate و static analysis موفق؛
- \(R\): runtime integration با WordPress/WooCommerce و HPOS موفق؛
- \(P\): contract test پیامک و پرداخت موفق؛
- \(E\): E2E مسیر درآمدی بدون شکست critical؛
- \(V\): p75 شاخص‌های Web Vitals در آستانه؛
- \(B\): backup و restore زمان‌دار موفق؛
- \(K\): rollback و cache invalidation rehearsal موفق.

در وضعیت فعلی فقط \(C=true\) اثبات شده است. برای همین:

\[
GoLive = false
\]

این نتیجه محافظه‌کارانه و قابل ممیزی است.

## ابزار بازتولید

محاسبات در `tests/quantitative_readiness.py` پیاده‌سازی شده‌اند و داده ساختگی تولید نمی‌کنند. اجرای فعلی:

```bash
./tests/quantitative_readiness.py \
  --root . \
  --out docs/verification-evidence/quantitative-readiness-2026-09-21.json
```

برای داده runtime واقعی باید CSV با ستون‌های `gate,total,failures,metric,threshold` از staging تهیه شود. نبود CSV عمداً در خروجی به‌عنوان `No runtime evidence CSV supplied` ثبت می‌شود.

## نتیجه نهایی

اصلاحات قابل انجام در repository انجام شد و محاسبات موردنیاز برای اثبات runtime آماده و قابل تکرار است. اما با داده فعلی، بالاترین نتیجه علمی ممکن **اثبات موفقیت code-level و عدم کفایت شواهد runtime** است. هر گزارشی که همین داده را به Go-Live قطعی تبدیل کند، از نظر آماری ادعای ناموجه خواهد بود.

## References

[1]: https://nvlpubs.nist.gov/nistpubs/Legacy/IR/nistir6129.pdf "NIST IR 6129 — Software Testing by Statistical Methods"
[2]: https://web.dev/articles/vitals-field-measurement-best-practices "Best practices for measuring Web Vitals in the field — web.dev"
[3]: https://web.dev/articles/vitals "Web Vitals — web.dev"
[4]: https://sre.google/workbook/implementing-slos/ "Implementing SLOs — Google SRE Workbook"
