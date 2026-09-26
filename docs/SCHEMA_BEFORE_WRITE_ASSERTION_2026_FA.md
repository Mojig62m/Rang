# Assertion گزارش: Schema Before Database Write — ۲۱ سپتامبر ۲۰۲۶

## هدف

هدف این کنترل آن است که هیچ مسیر نوشتن داده قبل از موفقیت schema validation به database، user meta، option، post، transient یا outbox write دست نزند. آزمون باید روی source واقعی پروژه انجام شود و نباید از mock، stub، fake، fixture یا پاسخ ساختگی استفاده کند.

## Invariant رسمی

برای هر مسیر اجرایی \(p\)، اگر \(W(p)\) اولین database write و \(V(p)\) موفقیت schema validation باشد، شرط لازم چنین است:

\[
\forall p \in ProductionPaths:\quad index(V(p)) < index(W(p))
\]

همچنین در حالت validation failure:

\[
V(p)=false \Rightarrow count(W(p))=0
\]

این invariant برای writeهای مستقیم WordPress، user creation، product seed، options، transients و notification outbox بررسی شده است.

## اصلاحات انجام‌شده

در mobile authentication، context قبلاً قبل از rate-limit transient به‌صورت whitelist بررسی نمی‌شد. اکنون `bamero_auth_schema_validate()` پیش از هر rate-limit، OTP transient update یا user write اجرا می‌شود. validator، شماره موبایل، context مجاز و در صورت وجود OTP، طول و ساختار شش‌رقمی را بررسی می‌کند.

در ثبت customer، شماره باید قبل از `wp_insert_user()` با الگوی normalized ایرانی معتبر شود و نام‌ها پیش از نوشتن sanitize و non-empty شوند.

در WooCommerce setup، اجرای activation اکنون با `bamero_wc_setup_require_schema()` شروع می‌شود. تمام seed، prune، user creation و option writes به guard `bamero_wc_setup_schema_is_validated()` وابسته‌اند. اگر schema معتبر نباشد، افزونه متوقف می‌شود و هیچ write انجام نمی‌دهد.

در production outbox، `bamero_notification_schema_validate()` پیش از هر outbox database operation اجرا می‌شود. template مجاز، mobile و OTP بررسی می‌شوند و سپس idempotency lookup و insert انجام می‌شود.

## آزمون بدون test double

فایل زیر source واقعی را می‌خواند، بدنه توابع را استخراج می‌کند و ترتیب validation و write را بررسی می‌کند:

```bash
bash tests/no_write_before_schema.sh
```

خروجی واقعی:

```text
PASS: 11 no-write-before-schema assertions; source-order checks only
PASS: invariant test has no test doubles
```

همچنین پس از اصلاحات:

```text
PHP lint: 21 files, PASS
Production gate: PASS
Static security checks: PASS
UI/UX checks: PASS
Email-free checks: PASS
```

## محدوده کنترل‌های پوشش‌داده‌شده

یازده assertion این مسیرها را پوشش می‌دهند: WooCommerce activation، product categories، product tags، catalog product creation، test-user creation، WooCommerce options، OTP request، OTP verification، customer registration، verify handler و notification outbox. این پوشش شامل مسیرهای اصلی write شناخته‌شده در repository است.

## محدودیت منطقی

این آزمون source-order و guard presence را اثبات می‌کند، اما به‌تنهایی نمی‌تواند اثر hookهای خارجی، pluginهای ناشناخته، SQL dynamic، رفتار database driver یا race در runtime واقعی را ثابت کند. برای اثبات runtime بدون mock باید staging واقعی با WordPress، WooCommerce، database و log transaction اجرا شود. بنابراین نتیجه این assertion:

> **Source invariant: PASS. Runtime universal proof: not claimed.**

این تفکیک لازم است؛ زیرا ادعای universal proof برای تمام execution pathهای ممکن WordPress قابل دفاع نیست.

## References

[1]: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/ "Adding Custom Endpoints — WordPress REST API Handbook"
[2]: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/ "HPOS extension recipe book — WooCommerce Developer Docs"
[3]: https://developer.wordpress.org/plugins/security/data-validation/ "Data Validation — WordPress Developer Resources"
