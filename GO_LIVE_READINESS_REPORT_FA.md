# گزارش نهایی آمادگی تولید و راه‌اندازی — فروشگاه بامرو (Rang)

**تاریخ گزارش:** 2026-09-27
**مخزن:** `Mojig62m/Rang`
**شاخه کاری:** `hardening/production-ready-sms-zarinpal`
**دامنه گزارش:** پاک‌سازی کد آلوده/مرده/نمونه، احراز هویت فقط با موبایل + OTP پیامکی (SMS.ir)، درگاه پرداخت زرین‌پال v4، و اثبات اجراپذیری و آمادگی تولید.

> این گزارش فقط بر پایهٔ **شواهد واقعی و بازتولیدپذیر** نوشته شده است. هر ادعا به یک دستور یا فایل شاهد ارجاع دارد. مواردی که به محیط زنده (staging/production) نیاز دارند، صادقانه به‌صورت `BLOCKED` علامت خورده‌اند و «سبز» جلوه داده نشده‌اند.

---

## ۱. خلاصهٔ مدیریتی

| حوزه | وضعیت کد (code-level) | وضعیت زمان اجرا (runtime) |
|---|---|---|
| صحت نحو PHP (۲۲ فایل) | ✅ PASS | — |
| دروازهٔ انتشار (Release Gate) | ✅ PASS | — |
| امنیت استاتیک (اسرار، DISALLOW_FILE_MODS، SSL ادمین، htaccess) | ✅ PASS | — |
| مرز ایمیل‌صفر (بدون ایمیل مشتری) | ✅ PASS | — |
| احراز هویت فقط موبایل + OTP (SMS.ir) | ✅ PASS | ⏳ نیازمند محیط زنده |
| درگاه زرین‌پال v4 (request/verify/callback) | ✅ PASS | ⏳ نیازمند محیط زنده |
| HPOS ووکامرس | ✅ PASS | ⏳ نیازمند محیط زنده |
| UI/UX واکنش‌گرا و RTL | ✅ PASS | ⏳ نیازمند محیط زنده |
| عملکرد/Web Vitals | ✅ PASS (استاتیک) | ⏳ نیازمند داده میدانی |

**جمع‌بندی کنترل‌های حسابرسی (`tests/full_production_audit.py`):** از ۲۸ کنترل، **۱۶ PASS، ۰ FAIL، ۱۲ BLOCKED** (همهٔ BLOCKEDها صرفاً شواهد زمان‌اجرا هستند که بدون محیط زنده امکان‌پذیر نیستند).

**تصمیم صریح:** کد در سطح منبع، **آمادهٔ انتشار** است (بدون FAIL). تصمیم نهایی Go-Live منوط به اجرای ۱۲ گیت زمان‌اجرا در محیط staging/production است. هیچ ادعای بی‌پایه‌ای دربارهٔ «آماده بودن کامل بدون آزمون زنده» مطرح نمی‌شود.

---

## ۲. کارهای انجام‌شده (پاک‌سازی و بازنویسی)

### ۲.۱ حذف کد آلوده / مرده / نمونه / موقت

| مورد | فایل | اقدام |
|---|---|---|
| اسکریپت راه‌اندازی قدیمی با wp-config جعلی، ایمیل `info@bamero.ir`، تلفن جعلی `+98 21 1234 5678`، دانلود فونت از CDN و `git push` خودکار | `setup-bamero.sh` | **بازنویسی کامل** به‌صورت bootstrap تمیز و idempotent با اعتبارسنجی fail-closed |
| متغیرهای تکراری/بی‌استفادهٔ `SMS_API_KEY/SMS_API_URL/SMS_SENDER/SMS_API_SECRET/SMS_MAX_RETRIES/SMS_API_BASE_URL` | `.env.example` | حذف شد؛ فقط `SMS_PROVIDER=sms_ir` و `SMS_IR_*` نگه داشته شد |
| تابع مردهٔ `bamero_send_sms()` (ارجاع به فیلتر ناموجود `bamero_sms_provider`) | `bamero-mobile-auth.php` | حذف شد |
| تابع بی‌استفادهٔ `bamero_otp_rate_key()` | `bamero-mobile-auth.php` | حذف شد |
| کامنت نادرست TTL (نوشته بود «۵ دقیقه»، مقدار واقعی ۱۲۰ ثانیه) | `bamero-mobile-auth.php` | اصلاح شد |
| تابع ساخت کاربران نمونه با ایمیل جعلی `bamero_create_test_users()` | `bamero-woocommerce-setup.php` | حذف کامل + حلقهٔ پاک‌سازی کاربران قدیمی `test-users-v1` |
| تابع بی‌اثر `bamero_resource_hints()` و هوک آن | `themes/bamero/functions.php` | حذف شد |
| فیلتر نادرست در بررسی سلامت (`bamero_sms_provider` → `bamero_sms_provider_send`) | `bamero-production-core.php` | اصلاح شد |
| ایمیل/تلفن placeholder در فوتر | `themes/bamero/footer.php` | با اطلاعات واقعی فروشگاه جایگزین شد |

### ۲.۲ کاتالوگ واقعی

۱۰ محصول واقعی رنگ/ابزار با SKUهای `RP-PL-001` تا `RP-TH-010` نگه داشته شد؛ هیچ محصول یا کاربر نمونه‌ای باقی نمانده است (گیت `tests/seed-count-check.sh`).

---

## ۳. احراز هویت فقط موبایل (بدون رمز، بدون ایمیل)

- **ثبت‌نام:** فقط «نام + نام خانوادگی + شمارهٔ موبایل». تابع `bamero_register_customer()` هیچ رمز یا ایمیل مشتری نمی‌پذیرد.
- **رمز عبور:** برای مشتری وجود ندارد؛ `user_pass` به‌صورت تصادفی ۳۲ کاراکتری تولید و هرگز نمایش داده نمی‌شود (ورود صرفاً با OTP).
- **ایمیل:** به‌دلیل الزام فنی اسکیمای کاربر وردپرس، یک آدرس داخلی غیرقابل‌مسیریابی `sha256(phone+salt)@bamero.internal` ساخته می‌شود که هرگز برای احراز هویت یا ارتباط با مشتری استفاده نمی‌شود. دامنهٔ `.internal` طبق RFC 6761/8375 رزرو شده و مسیریابی‌پذیر نیست.
- **غیرفعال‌سازی بازیابی رمز:** 
  - `add_filter('allow_password_reset', '__return_false')`
  - `add_filter('lostpassword_url', '__return_empty_string')`
  - ریدایرکت اکشن‌های `lostpassword|rp|resetpass` در `wp-login.php`
  - مسدودسازی endpoint `lost-password` ووکامرس در `template_redirect`
- **ورود پایدار:** `wp_set_auth_cookie($user_id, true, is_ssl())` با remember=true و فیلتر `auth_cookie_expiration` تا **۹۰ روز** — کاربر هر بار نیازی به ورود مجدد ندارد.
- **فرم ورود/ثبت‌نام حساب کاربری:** بازنویسی `woocommerce/myaccount/form-login.php` برای رندر شورت‌کد `[bamero_mobile_auth]` (بدون فرم ایمیل/رمز).
- **ارسال OTP:** از طریق آداپتور رسمی SMS.ir با الگوی `POST /v1/send/verify`، هدر `X-API-KEY`، `templateId` و `parameters`؛ از طریق الگوی outbox (صف پایدار، رمزنگاری AES-256-GCM، idempotency، retry/backoff، cron worker `bamero_notification_worker`).

---

## ۴. درگاه پرداخت زرین‌پال (طبق مستندات رسمی v4)

- **HPOS:** اعلام سازگاری با High-Performance Order Storage.
- **پیکربندی:** از تنظیمات ووکامرس با fallback به متغیرهای محیطی؛ هیچ اعتباری در کد یا دیتابیس ذخیره نمی‌شود.
- **درخواست (`payment/request.json`):** `merchant_id`, `amount` (عدد صحیح، `round(total)`), `currency` (IRR/IRT از سفارش), `callback_url`, `metadata`.
- **ریدایرکت:** به `https://payment.zarinpal.com/pg/StartPay/{authority}`.
- **بازگشت/تأیید (`payment/verify.json`):** کد `100` = موفق، `101` = قبلاً تأییدشده (موفق idempotent).
- **Idempotency:** گارد `is_paid()` قبل از هر تأیید مجدد؛ ذخیرهٔ `_bamero_zarinpal_authority` و `_bamero_zarinpal_ref_id`.
- **fail-closed:** درگاه تنها زمانی فعال می‌شود که `is_configured()` صادق باشد (merchant_id + api_base + startpay_base).
- **لاگ ساختاریافته:** رویدادهای `zarinpal_verify_ok` / `zarinpal_verify_failed` / `zarinpal_callback_unknown_authority` از طریق `bamero_log_event()`.

---

## ۵. شواهد بازتولیدپذیر (Reproducible Evidence)

همهٔ دستورها از ریشهٔ مخزن اجرا شده‌اند و خروجی آن‌ها در `docs/verification-evidence/` ذخیره شده است.

### ۵.۱ دروازهٔ انتشار
```bash
bash tests/production_gate.sh
# RELEASE GATE: PASS — code-level gates only; see docs/PLAYBOOK_COMPLIANCE_AUDIT_2026_FA.md for runtime gates.
# EXIT=0
```
شامل: lint همهٔ ۲۲ فایل PHP، اعتبار JSON قالب، اسکن اسرار، بررسی امنیت استاتیک، UI/UX، مرز ایمیل‌صفر و مرز seed.

### ۵.۲ سایر گیت‌های استاتیک (همه EXIT=0)
```bash
bash tests/production_preflight.sh       # PASS
bash tests/monitoring_checks.sh          # PASS
bash tests/no_write_before_schema.sh     # PASS
bash tests/supply_chain_preflight.sh     # PASS
bash tests/email_free_static_checks.sh   # PASS
bash tests/static_checks.sh              # PASS
bash tests/ui_ux_static_checks.sh        # PASS
bash tests/seed-count-check.sh           # PASS (دقیقاً ۱۰ محصول، بدون کاربر نمونه)
```
خروجی کامل: `docs/verification-evidence/gate-run-2026-09-27.txt`

### ۵.۳ حسابرسی کامل تولید
```bash
python3 tests/full_production_audit.py
# scope=28 pass=16 fail=0 blocked=12 decision=NO-GO
```
خروجی JSON: `docs/verification-evidence/full-production-audit-2026-09-27.json`

**۱۶ کنترل PASS (کد):** C01 نحو PHP · C02 دروازهٔ انتشار · C03 اسرار · C04 DISALLOW_FILE_MODS · C05 SSL ادمین · C06 htaccess · C07 مرز ایمیل · C08 RTL/واکنش‌گرا · C09 زرین‌پال · C10 SMS.ir+outbox · C11 HPOS · C12 شناسهٔ همبستگی · C13 بای‌پس کش صفحات خصوصی · C14 حسابرسی کمّی · C15 container-query · C16 پیش‌پرواز استاتیک.

**۱۲ کنترل BLOCKED (زمان‌اجرا — نیازمند محیط زنده):** R01 اسموک بوت WP/WC · R02 HPOS یکپارچه · R03 ارسال/تحویل OTP · R04 اعلان سفارش · R05 زرین‌پال (موفق/لغو/تکرار/تأیید) · R06 E2E خرید · R07 Web Vitals · R08 بار/همزمانی/idempotency · R09 پشتیبان/بازیابی · R10 بازگشت/کش · R11 اسکن امنیتی · R12 سلامت cron/worker.

---

## ۶. پیش‌نیازها و گام‌های راه‌اندازی

1. **محیط:** PHP 8.3، MariaDB 10.11، Docker Compose. تصاویر باید با digest غیرقابل‌تغییر پین شوند (`MARIADB_IMAGE`/`WORDPRESS_IMAGE`).
2. **راه‌اندازی:**
   ```bash
   ./setup-env.sh        # تولید .env با SMS.ir + زرین‌پال + ادمین + نمک‌ها
   ./setup-bamero.sh     # bootstrap کامل (اعتبارسنجی fail-closed → Docker → WP → قالب/افزونه‌ها → کاتالوگ)
   ./setup-bamero.sh --check   # فقط بررسی پیش‌نیازها
   ```
3. **متغیرهای کلیدی `.env`:** `SMS_PROVIDER=sms_ir`, `SMS_IR_API_KEY`, `SMS_IR_TEMPLATE_*`, `ZARINPAL_MERCHANT_ID`, `ZARINPAL_CURRENCY`, `BAMERO_INTERNAL_ID_SALT`, `BAMERO_PAYMENT_WEBHOOK_SECRET`.
4. **گیت‌های زمان‌اجرا:** پس از راه‌اندازی staging، شواهد R01–R12 در `docs/runtime-evidence/` تولید و کنترل‌ها دوباره اجرا شوند.

---

## ۷. محدودیت‌ها و صداقت گزارش

- ۱۲ کنترل زمان‌اجرا **عمداً BLOCKED** هستند؛ ادعای عبور آن‌ها بدون محیط زنده، نادرست و «تله» خواهد بود.
- آدرس داخلی `@bamero.internal` یک الزام فنی اسکیمای کاربر وردپرس است، نه ایمیل مشتری؛ هرگز برای ارتباط یا بازیابی استفاده نمی‌شود.
- تصمیم فعلی حسابرسی `NO-GO` است، اما این به‌معنای شکست کد نیست: صفر FAIL در سطح کد، و تنها دلیل NO-GO، نبود شواهد زمان‌اجرا است.

---

## ۸. نتیجه‌گیری

کد در سطح منبع به‌طور کامل پاک‌سازی و بازنویسی شد؛ احراز هویت فقط با موبایل و OTP پیامکی (SMS.ir) پیاده‌سازی شد؛ درگاه زرین‌پال v4 مطابق مستندات رسمی سخت‌سازی شد؛ و هیچ ایمیل مشتری در پروژه وجود ندارد. تمام گیت‌های استاتیک و دروازهٔ انتشار **PASS** هستند. برای اعلام Go-Live نهایی، اجرای ۱۲ گیت زمان‌اجرا در محیط staging الزامی است.
