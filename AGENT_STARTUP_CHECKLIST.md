# ✅ چک‌لیست راه‌اندازی Copilot Coding Agent

**موضوع:** تأیید شرایط شروع، بررسی فرایند، و کنترل قبل‌از شروع هر ایشو

**تاریخ:** 2026-10-17  
**وضعیت:** فعال — **اجرا قبل‌از هر کاری**

---

## 🔄 فرایند دو مرحله‌ای

```
┌─────────────────────────────────────────┐
│ فاز ۱: تأیید محیط و شرایط شروع         │
├─────────────────────────────────────────┤
│ ✓ فایل قرارداد در mخزن موجود است؟   │
│ ✓ main branch تازه است؟               │
│ ✓ production_gate.sh pass می‌کند؟    │
│ ✓ PR‌های باز بلاک‌کنندهٔ موجودند؟   │
└─────────────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│ فاز ۲: تأیید ایشو قابل اختصاص         │
├─────────────────────────────────────────┤
│ ✓ عنوان واضح است؟                     │
│ ✓ شرح مشکل/هدف دقیق است؟             │
│ ✓ معیار قبول روشن است؟                │
│ ✓ فایل‌های متأثر مشخص‌اند؟           │
│ ✓ پیچیدگی Low–Medium است؟             │
│ ✓ وابستگی به ایشوهای دیگر ندارد؟     │
└─────────────────────────────────────────┘
                   ↓
         شروع کار ✅ یا توقف ❌
```

---

## فاز ۱: تأیید محیط (یک‌بار در شروع)

### ۱.۱ بررسی فایل قرارداد

**دستور:**
```bash
curl -s https://raw.githubusercontent.com/Mojig62m/Rang/main/.github/copilot-agent-contract.md \
  | head -50 | grep -q "Copilot Coding Agent" && echo "✅ قرارداد یافت شد" \
  || echo "❌ قرارداد یافت نشد"
```

**انتظار:** `✅ قرارداد یافت شد`

**اگر fail شود:**
```
❌ خطا: فایل .github/copilot-agent-contract.md در main یافت نشد.

→ کاری نکنید تا فایل merge شود.
→ این فایل به‌عنوان منبع حقیقت است برای تمام تصمیمات.

Contact: Mojig62m
```

---

### ۱.۲ بررسی وضعیت main

**دستور:**
```bash
echo "🔍 بررسی آخرین commit main..."
git fetch origin main 2>/dev/null

last_commit=$(git log -1 --format="%H %s" origin/main 2>/dev/null)
if [ -z "$last_commit" ]; then
  echo "❌ خطا: fetch ناموفق. اتصال internet بررسی کنید."
  exit 1
fi

echo "✅ آخرین commit:"
echo "   $last_commit"

# بررسی اینکه آخرین commit draft یا WIP نیست
echo "$last_commit" | grep -qi "draft\|wip\|todo" && {
  echo "⚠️  هشدار: آخرین commit شامل WIP/Draft است."
  echo "   منتظر merge branch بمانید."
  exit 1
} || echo "✅ commit معمولی است"
```

**انتظار:** `✅ آخرین commit` و `✅ commit معمولی است`

---

### ۱.۳ اجرای production_gate.sh

**دستور:**
```bash
echo "🧪 اجرای production_gate.sh..."

git clone https://github.com/Mojig62m/Rang.git /tmp/rang-main 2>&1 | tail -3
cd /tmp/rang-main
git checkout origin/main 2>/dev/null

if [ ! -f "tests/production_gate.sh" ]; then
  echo "❌ خطا: tests/production_gate.sh یافت نشد"
  exit 1
fi

bash tests/production_gate.sh
gate_result=$?

if [ $gate_result -eq 0 ]; then
  echo "✅ production_gate.sh: موفق"
else
  echo "❌ production_gate.sh: ناموفق (کد: $gate_result)"
  echo ""
  echo "→ main branch حالاً go-live compatible نیست."
  echo "→ نخستین priority: fixing gate failures است."
  exit 1
fi
```

**انتظار:** `✅ production_gate.sh: موفق`

**اگر fail شود:**
```
❌ Gate ناموفق

→ تا زمانی که main gate را pass نکند، ایجنت نباید کار کند.
→ این نشانهٔ مشکل بنیادی است.
```

---

### ۱.۴ بررسی PRهای باز همپوشان

**دستور:**
```bash
echo "🔍 بررسی PRهای باز..."

open_prs=$(curl -s "https://api.github.com/repos/Mojig62m/Rang/pulls?state=open" \
  | grep -o '"number": [0-9]*' | head -20)

if [ -z "$open_prs" ]; then
  echo "✅ هیچ PR بازی نیست"
else
  echo "⚠️  PRهای باز: $open_prs"
  echo ""
  echo "→ اگر موضوع/فایل‌ها با ایشو همپوشان است: توقف"
  echo "→ اگر مستقل است: ادامه با Draft PR"
fi
```

**انتظار:** `✅ هیچ PR بازی نیست` یا `⚠️` (مشروط — فقط PR همپوشان مسدودکننده است)

> تاریخچه: نسخهٔ ۱.۰ به PRهای #7/#18/#21 اشاره داشت که همگی بسته شده‌اند؛ از ۲۰۲۶-۱۰-۱۷ قاعده پویاست.

---

## فاز ۲: تأیید ایشو (برای هر ایشو جدید)

### ۲.۱ بررسی عنوان

**معیار:**
```
✅ صفحهٔ اول: عنوان واضح است؟
   - شامل نوع (fix, refactor, test, docs, etc.)
   - شامل scope (theme, plugin, ci, etc.)
   - شامل شرح کوتاه فارسی یا انگلیسی

مثال صحیح:
  ✅ "fix(theme): درست‌کردن offset button موبایل RTL"
  ✅ "refactor(plugin): حذف dead code از main.js"
  ✅ "test(ci): اضافه‌کردن regression test برای csp_nonce"

مثال غلط:
  ❌ "fix: مشکل"
  ❌ "work in progress"
  ❌ "urgent"
```

**دستور تأیید:**
```bash
echo "📋 بررسی عنوان ایشو..."

issue_title="$1"  # عنوان ایشو

# بررسی اینکه شامل fix/refactor/test/etc است
echo "$issue_title" | grep -qiE "^(fix|feat|refactor|test|docs|ci|perf|style|chore)\(" && {
  echo "✅ عنوان معیار Conventional Commits است"
} || {
  echo "❌ عنوان از معیار Conventional Commits پیروی نمی‌کند"
  echo "   الگو: type(scope): description"
  exit 1
}

# بررسی اینکه شامل WIP یا TODO نیست
echo "$issue_title" | grep -qiE "wip|todo|draft" && {
  echo "❌ عنوان شامل WIP/TODO است — این ایشو کامل نیست"
  exit 1
} || echo "✅ بدون WIP/TODO"
```

---

### ۲.۲ بررسی شرح مشکل/هدف

**معیار:**
```
✅ ایشو شامل توضیح دقیق است؟
   - اگر fix: چه مشکلی است و چگونه تشخیص داده می‌شود؟
   - اگر feature: چرا لازم است و فائدهٔ آن چه است؟

مثال صحیح:
  ✅ "موبایل RTL (۳۲۰px) میں button offset غلط است. 
     شواهد: screenshot."
  ✅ "غیرفعال کردن XML-RPC برای امنیت. 
     مرجع: SECURITY.md."

مثال غلط:
  ❌ "something is broken"
  ❌ بدون توضیح
```

**دستور تأیید:**
```bash
echo "📝 بررسی شرح ایشو..."

issue_body="$1"  # بدنهٔ ایشو

# بررسی حداقل طول
word_count=$(echo "$issue_body" | wc -w)
if [ "$word_count" -lt 10 ]; then
  echo "❌ شرح بیش‌ازحد کوتاه است ($word_count کلمه)"
  echo "   حداقل ۱۰ کلمه دقیق توضیح لازم است"
  exit 1
fi

echo "✅ شرح شامل توضیح دقیق است ($word_count کلمه)"
```

---

### ۲.۳ بررسی معیار قبول

**معیار:**
```
✅ ایشو شامل معیار قبول است؟
   - فایل‌های متأثر شده
   - تست‌های مورد انتظار
   - شواهد دستی (برای اشیاء غیرخودکار)

مثال صحیح:
  ✅ "Acceptance Criteria:
     1. فایل: wp-content/themes/bamero/css/style.css
     2. تست: production_gate.sh pass
     3. شاهد: موبایل RTL ۳۲۰px - تأیید شد"

مثال غلط:
  ❌ "هر کاری انجام دهید"
  ❌ بدون معیار واضح
```

**دستور تأیید:**
```bash
echo "✓ بررسی معیار قبول..."

# بررسی فایل‌های متأثر
if echo "$issue_body" | grep -qi "file\|فایل"; then
  echo "✅ شامل فایل‌های متأثر"
else
  echo "⚠️  شامل فایل‌های متأثر نیست (اختیاری اما توصیه شده)"
fi

# بررسی تست
if echo "$issue_body" | grep -qi "test\|تست"; then
  echo "✅ شامل تست‌ها"
else
  echo "⚠️  شامل تست نیست (اختیاری اما توصیه شده)"
fi
```

---

### ۲.۴ بررسی پیچیدگی

**معیار:**
```
✅ پیچیدگی Low–Medium است؟

LOW: 1–2 فایل، بدون منطق جدید
  ✅ درست‌کردن typo
  ✅ تغییر رنگ CSS
  ✅ حذف dead code سادهٔ ۱ فایل

MEDIUM: 2–4 فایل، منطق کوچک
  ✅ حذف dead code در چند فایل
  ✅ اضافه‌کردن واحد کوچک
  ✅ refactor محدود

HIGH: 5+ فایل یا تغییر معماری
  ❌ نباید برای ایجنت باشد
  ❌ نیاز به بررسی دستی است
```

**دستور تأیید:**
```bash
echo "📊 بررسی پیچیدگی..."

affected_files=$(echo "$issue_body" | grep -oiE "file|فایل" | wc -l)

if [ "$affected_files" -eq 0 ]; then
  echo "❓ تعداد فایل‌های متأثر نامشخص است"
  echo "   توصیه: ایشو را بهتر توضیح دهید"
elif [ "$affected_files" -le 2 ]; then
  echo "✅ پیچیدگی LOW ($affected_files فایل)"
elif [ "$affected_files" -le 4 ]; then
  echo "✅ پیچیدگی MEDIUM ($affected_files فایل)"
else
  echo "❌ پیچیدگی HIGH ($affected_files فایل) — بیش‌ازحد برای ایجنت"
  exit 1
fi
```

---

> ⚠️ نکته: شمارش با grep روی کلمات «file/فایل» تقریبی است؛ عدد معتبر را از «معیار قبول» ایشو (لیست فایل‌های متأثر) بگیرید.

### ۲.۵ بررسی وابستگی‌های ایشو

**معیار:**
```
✅ ایشو مستقل است؟
   - وابستگی به ایشوی دیگری ندارد؟
   - prerequisite برای ایشوی دیگری نیست؟

مثال صحیح:
  ✅ "تصحیح button offset (مستقل)"

مثال غلط:
  ❌ "بعد از حل ایشوی #15، این کار را انجام دهید"
  ❌ "این depends on PR #7 است"
```

**دستور تأیید:**
```bash
echo "🔗 بررسی وابستگی‌ها..."

if echo "$issue_body" | grep -qiE "depend|prerequisite|after.*issue|اگر.*حل شود"; then
  echo "⚠️  ایشو وابستگی دارد:"
  echo "$issue_body" | grep -iE "depend|prerequisite|after|اگر"
  echo ""
  echo "→ منتظر حل وابستگی بمانید یا موضوع را شفاف‌تر کنید"
  exit 1
else
  echo "✅ ایشو مستقل است"
fi
```

---

## ✅ خلاصهٔ تأیید

```bash
#!/bin/bash

echo "════════════════════════════════════════════"
echo "   فاز ۱: تأیید محیط"
echo "════════════════════════════════════════════"

# ۱.۱ قرارداد
check_contract

# ۱.۲ main branch
check_main_status

# ۱.۳ production_gate
run_production_gate

# ۱.۴ PR‌های بلاک‌کننده
check_blocking_prs

echo ""
echo "✅ محیط تأیید شد!"
echo ""
echo "════════════════════════════════════════════"
echo "   فاز ۲: برای هر ایشو"
echo "════════════════════════════════════════════"
echo ""
echo "بعد از اختصاص ایشو:"
echo ""
echo "  1. عنوان را بررسی کنید"
echo "  2. شرح را بررسی کنید"
echo "  3. معیار قبول را بررسی کنید"
echo "  4. پیچیدگی را بررسی کنید"
echo "  5. وابستگی‌ها را بررسی کنید"
echo ""
echo "اگر همه ✅ → شروع کار"
echo "اگر هر یک ❌ → توقف و پاسخ دهید"
echo ""
```

---

## 🚀 دستور اجرا

### برای ایجنت (GitHub Copilot):

```bash
# قبل از شروع هر کاری:
فاز ۱ به‌صورت اجرایی در دسترس است: `bash scripts/agent_preflight.sh` یا `make preflight` (خروجی ۰ = GO، ۱ = STOP؛ fail-closed)

# برای هر ایشو جدید:
source AGENT_STARTUP_CHECKLIST.md
check_issue_criteria "$issue_title" "$issue_body"
```

### برای انسان (بررسی دستی):

```bash
# بررسی محیط
curl -s https://raw.githubusercontent.com/Mojig62m/Rang/main/.github/copilot-agent-contract.md | head -50

# بررسی gate
git clone https://github.com/Mojig62m/Rang.git
cd Rang
bash tests/production_gate.sh

# بررسی PR‌ها
gh pr list --state open | grep -E "#7|#18|#21"
```

---

## 📞 سؤالات متکررة

**Q: چگونه می‌دانم کدام ایشو‌ها برای assign کردن آماده‌اند؟**

A: بعد از pass کردن فاز ۱ (محیط)، هر ایشویی که:
- ✅ عنوان واضح است
- ✅ شرح دقیق است
- ✅ معیار قبول روشن است
- ✅ پیچیدگی Low–Medium است
- ✅ مستقل است

**Q: اگر ایشو معیارها را pass نکند، چه کار کنم؟**

A: پاسخ دهید:
```
متأسفانه این ایشو هنوز برای کار خودکار آماده نیست:

❌ دلیل: [معیاری که fail شد]

→ لطفاً ایشو را بهتر توضیح دهید:
  - فایل‌های متأثر را دقیق‌تر ذکر کنید
  - معیار قبول را روشن‌تر کنید
  - وابستگی‌های آن را توضیح دهید

بعد از بهتر‌شدن، دوباره assign کنید.
```

**Q: اگر فاز ۱ fail شود چه؟**

A: توقف فوری:
```
❌ محیط آماده نیست

❌ دلیل: [مشکل]

→ تا زمانی که این مسئله حل نشود، ایجنت شروع نمی‌شود.
```

---

## 📋 فایل‌های مرجع

- **قرارداد اجرایی:** `.github/copilot-agent-contract.md`
- **gate:** `tests/production_gate.sh`
- **smoke test:** `tests/staging_smoke.php`
- **مستندات:** `OPERATIONAL_READINESS_FA.md`

