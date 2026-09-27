# BAMERO-REFINE-HOSTING-READY — گزارش تفاوت فایل‌سیستم (Sanitize Diff)

**تاریخ:** 2026-09-27 · **شاخه:** `hosting-ready/refine`
**پایه:** 152 فایل ردیابی‌شده → **پس از Sanitize: 118 فایل** (۳۶ حذف، ۲ افزودن)

---

## ۱. حذف‌شده‌ها (۳۶ فایل)

### Docker / Compose
- `docker-compose.yml`
- `docker-compose.production.yml`

### CI/CD (test-gate)
- `.github/workflows/production-gate.yml` → با `deploy-hosting.yml` جایگزین شد

### اسکریپت‌های Bootstrap محلی
- `setup-bamero.sh`
- `setup-env.sh`
- `.env.export.sh`

### Staging (Compose + seed)
- `staging/README.md`
- `staging/provision-catalog.sh`

### Test Fixtures / Scripts (۱۶ فایل)
- `tests/*.py` (۵ فایل) و `tests/*.sh` (۱۱ فایل)

### UI Preview (Dev)
- `ui-preview/index.html`, `ui-preview/make_contact_sheet.py`, `ui-preview/visual-findings.md`
- `ui-preview/screenshots/*.png` (۹ تصویر)

### Seed Script (کاربران نمونه)
- `wp-content/themes/bamero/tools/provision-bamero-catalog.php` (شامل ۱۰ کاربر نمونه با شماره‌های جعلی)

---

## ۲. افزوده‌ها (۲ فایل)

- `.github/workflows/deploy-hosting.yml` — pipeline استقرار میزبانی (rsync/SSH، بدون build کانتینر روی هاست؛ artifact پاک‌سازی‌شده می‌سازد و نشت آرتیفکت dev را fail-closed رد می‌کند).
- `docs/HOSTING_DISCOVERY_MANIFEST_FA.md` — مانیفست فاز DISCOVER.

---

## ۳. تغییرات (Modified)

- `.htaccess` — افزودن مسدودسازی `docs/` و پسوندهای `.md/.markdown/.rst/.sh/.lock/.yml/.yaml` (دفاع لایه‌ای؛ همچنین در deploy مستثنا می‌شوند).
- `.env.example` — بازنویسی: فقط متغیرهای مصرفی واقعی runtime؛ حذف متغیرهای bootstrap بی‌استفاده (`DOMAIN`, `WP_ADMIN_*`, `DB_ROOT_PASSWORD`)؛ افزودن `ZARINPAL_STARTPAY_URL`.
- `todo.md` — برنامهٔ اجرایی این دستور.

---

## ۴. تأیید عدم نشت اسرار (Post-Sanitize)

اسکن الگوهای کلید خصوصی/توکن/کلید API: **هیچ سرّ واقعی یافت نشد**. `.gitignore` موارد `.env`, `*.key`, `*.pem`, `*.p12`, `*.pfx`, `*.log` را مستثنا می‌کند.

---

## ۵. بررسی ارجاعات معلق (Dangling References)

تنها ارجاع باقی‌مانده به اسکریپت‌های حذف‌شده در `.env.example` بود که اصلاح شد. ارجاعات داخل فایل‌های `.md` صرفاً مستندات تاریخی‌اند و روی runtime اثری ندارند.

---

## ۶. نتیجهٔ فاز SANITIZE

| معیار | وضعیت |
|---|---|
| حذف Docker/K8s/Vagrant/compose | ✅ |
| حذف CI/CD تست (جایگزینی با pipeline استقرار) | ✅ |
| حذف test fixtures/seed/debug/dev-preview | ✅ |
| پاک‌سازی wp-config.php | ✅ (از قبل env-based و بدون ثابت تستی) |
| پاک‌سازی .htaccess | ✅ (بدون dev rewrite؛ افزودن محافظت) |
| عدم وجود سرّ در کد/config/log | ✅ |
| گزارش تفاوت قبل/بعد | ✅ (همین سند) |
