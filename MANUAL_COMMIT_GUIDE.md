# راهنمای دستی: Commit فایل قرارداد Copilot Agent

فایل `.github/copilot-agent-contract.md` آماده است. این راهنما نحوهٔ کامیت آن را نشان می‌دهد.

---

## گزینهٔ ۱: استفاده از GitHub Web Editor (سریع‌ترین)

### مراحل:

1. به **GitHub** بروید:
   ```
   https://github.com/Mojig62m/Rang
   ```

2. کلیک کنید بر روی **`Add file` → `Create new file`**

3. در قسمت **"Name your file"**:
   ```
   .github/copilot-agent-contract.md
   ```

4. **محتوا را بچسبانید** (فایل `copilot-agent-contract.md` از این دسته)

5. در پایین، **"Commit message"**:
   ```
   docs(ci): افزودن قرارداد اجرایی برای Copilot Coding Agent
   ```

6. **Optional description**:
   ```
   - تعریف معیار وضوح و مناسب‌بودن ایشو برای کار خودکار
   - ارجاع دقیق به gate‌های موجود (production_gate.sh, staging_smoke.php)
   - شرط دسترسی‌ناپذیری برای PR‌های باز مرتبط (#7, #18, #21)
   - الزامات شواهد برای merge‌پذیری (OPERATIONAL_READINESS_FA.md)
   - قوانین fail-closed و نمونهٔ فرایند کامل
   
   این سند به منابع و فرایند‌های موجود ارجاع می‌دهد (نه توصیه‌های کلی).
   ```

7. کلیک کنید **"Commit new file"**

---

## گزینهٔ ۲: استفاده از Git CLI (ترمینال)

### پیش‌نیاز:
- Git نصب شده و configured
- GitHub credentials (SSH یا token)

### مراحل:

```bash
# ۱. Clone مخزن (اگر قبلاً نشده)
git clone https://github.com/Mojig62m/Rang.git
cd Rang

# ۲. آپدیت کردن main
git fetch origin main
git checkout main
git pull origin main

# ۳. ایجاد branch جدید
git checkout -b docs/copilot-agent-contract

# ۴. ایجاد پوشهٔ .github (اگر موجود نیست)
mkdir -p .github

# ۵. کپی فایل
cp /path/to/copilot-agent-contract.md .github/copilot-agent-contract.md

# ۶. Stage کردن
git add .github/copilot-agent-contract.md

# ۷. Commit کردن
git commit -m "docs(ci): افزودن قرارداد اجرایی برای Copilot Coding Agent

- تعریف معیار وضوح و مناسب‌بودن ایشو برای کار خودکار
- ارجاع دقیق به gate‌های موجود (production_gate.sh, staging_smoke.php)
- شرط دسترسی‌ناپذیری برای PR‌های باز مرتبط (#7, #18, #21)
- الزامات شواهد برای merge‌پذیری (OPERATIONAL_READINESS_FA.md)
- قوانین fail-closed و نمونهٔ فرایند کامل

این سند به منابع و فرایند‌های موجود ارجاع می‌دهد (نه توصیه‌های کلی)."

# ۸. Push به GitHub
git push -u origin docs/copilot-agent-contract

# ۹. ایجاد Pull Request (از GitHub Web یا CLI)
# از طریق GitHub Web:
#   https://github.com/Mojig62m/Rang/pull/new/docs/copilot-agent-contract
#
# یا با GitHub CLI:
gh pr create --base main --head docs/copilot-agent-contract \
  --title "docs(ci): قرارداد اجرایی برای Copilot Coding Agent" \
  --body "
## خلاصه
اضافه‌کردن معیار، فرایند و الزامات شواهد برای اختصاص ایشو‌ها به Copilot Coding Agent.

## محتوا
- **معیار وضوح:** عنوان واضح، شرح دقیق، معیار قبول
- **شرط‌های دسترسی‌ناپذیری:** بررسی PR‌های باز #7، #18، #21
- **فرایند قبل‌از کار:** gate check، branch creation، commit standards
- **شواهد الزامی:** production_gate.sh، documentation sync، PR description
- **قوانین fail-closed:** توقف درصورت gate failure یا ambiguity
- **نمونهٔ کامل:** سناریوی button offset موبایل

## شواهد
- ✅ این سند **کد نیست**؛ صرفاً ارجاع به منابع موجود
- ✅ **گسترش نیست**؛ سنجش و تنظیم فرایند‌های current
- ✅ **ادعایی نیست**؛ تمام ارجاع‌ها به gate‌ها، مستندات و script‌های واقعی
"
```

---

## گزینهٔ ۳: GitHub Desktop (GUI)

### مراحل:

1. **Clone کنید:**
   - باز کنید GitHub Desktop
   - `File` → `Clone repository`
   - `Mojig62m/Rang` را انتخاب کنید
   - Clone کنید

2. **ایجاد branch:**
   - کلیک کنید `Current Branch`
   - کلیک کنید `New Branch`
   - نام: `docs/copilot-agent-contract`
   - Base: `main`
   - کلیک کنید `Create Branch`

3. **اضافه کردن فایل:**
   - باز کنید Finder/Explorer
   - پوشهٔ `Rang` > `.github` را بسازید (اگر موجود نیست)
   - فایل `copilot-agent-contract.md` را کپی کنید به `.github/`

4. **Commit:**
   - بازگردید GitHub Desktop
   - فایل باید در **Changes** دیده بشود
   - جعبهٔ **Summary**: `docs(ci): افزودن قرارداد اجرایی برای Copilot Coding Agent`
   - جعبهٔ **Description** (اختیاری):
     ```
     - تعریف معیار وضوح و مناسب‌بودن ایشو برای کار خودکار
     - ارجاع دقیق به gate‌های موجود (production_gate.sh, staging_smoke.php)
     - شرط دسترسی‌ناپذیری برای PR‌های باز مرتبط (#7, #18, #21)
     - الزامات شواهد برای merge‌پذیری (OPERATIONAL_READINESS_FA.md)
     ```
   - کلیک کنید `Commit to docs/copilot-agent-contract`

5. **Push و PR:**
   - کلیک کنید `Publish branch`
   - رفتید GitHub.com، PR ایجاد کنید

---

## محتوای PR (الگو)

### عنوان:
```
docs(ci): قرارداد اجرایی برای Copilot Coding Agent
```

### بدنهٔ PR:
```markdown
## خلاصه
اضافه‌کردن معیار، فرایند و الزامات شواهد برای اختصاص ایشو‌ها به Copilot Coding Agent.

## چرا این فایل؟
- **بدون گسترش:** ارجاع به gate‌ها و مستندات موجود، نه ایجاد نو
- **بدون ادعای نو:** شامل فقط چیزهایی است که **قبلاً در مخزن موجود است**
- **عملیاتی:** شامل معیار عینی، فرایند قابل‌تکرار و نمونهٔ کامل

## محتوای کلیدی
- ✅ **۲. معیار وضوح:** عنوان واضح، شرح دقیق، معیار قبول، پیچیدگی Low–Medium
- ✅ **۳. شرط دسترسی‌ناپذیری:** بررسی خودکار PR‌های باز (#7, #18, #21)
- ✅ **۴. فرایند قبل‌از کار:** gate check، branch creation، commit standards
- ✅ **۵. شواهد الزامی:** production_gate.sh، documentation sync، PR description
- ✅ **۶. قوانین fail-closed:** توقف درصورت gate failure
- ✅ **۷. شواهد اجرایی:** جدول منابع حقیقت
- ✅ **۸. نمونهٔ کامل:** سناریوی button offset موبایل از شروع تا merge

## تأیید نشده (و مجاز)
- [ ] جزئیات هاستینگ (توسط فایل‌های دیگر تکمیل خواهد شد)
- [ ] نسخهٔ نهایی Lighthouse (standalone topic)
- [ ] E2E test زرین‌پال (محدود به staging)

## اطلاعات اضافی
- فایل: `.github/copilot-agent-contract.md`
- شامل: ۱۱ بخش، نمونهٔ bash، مثال‌های فارسی
- بدون وابستگی جدید
```

---

## نکاتی برای مراقبت

### ✅ نکاتی که باید حفظ کنید:
- **PR description مفصل** (شامل دلیل اضافه‌کردن این فایل)
- **Commit message معیار** (Conventional Commits)
- **هیچ تغییر دیگری** در این commit (صرف `.github/copilot-agent-contract.md`)

### ❌ چیزهایی که نباید کنید:
- تغییر دستور PR‌های باز (#7, #18, #21)
- اصلاح gate‌ها یا script‌ها (آن‌ها قبلاً بررسی شده‌اند)
- اضافه‌کردن توصیه‌های جدید فراتر از فرایند موجود

---

## پس از Merge

هنگامی که PR merge شد:

1. **بررسی کنید:**
   ```bash
   git checkout main
   git pull origin main
   cat .github/copilot-agent-contract.md | head -20
   ```

2. **به ایجنت اعلام کنید:**
   - پیام GitHub Discussion یا Issue:
     ```
     Copilot Agent contract شامل است در .github/copilot-agent-contract.md
     قبل از assign کردن ایشو، این فایل را بخوانید.
     ```

3. **شروع استفاده:**
   - هر ایشوی جدیدی که برای assign کردن آماده است، آن را برای ایجنت علامت‌گذاری کنید

---

## سؤالات؟

اگر مشکلی در commit داشتید:
- مطمئن شوید مجوزهای GitHub صحیح است (SSH key یا token)
- مطمئن شوید `main` branch تا آخرین commit update شده است
مطمئن شوید فایل `.github/copilot-agent-contract.md` موجود و با نسخهٔ ریشهٔ مخزن یکسان است (از ۲۰۲۶-۱۰-۱۷ هر دو موجودند)
