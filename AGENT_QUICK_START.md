# ⚡ شروع سریع برای Copilot Agent

**خلاصهٔ ۳۰ ثانیه‌ای — همه چیزی که باید یک‌بار بدانید**

---

## 📋 قبل از هر کاری

```
✅ قرارداد .github/copilot-agent-contract.md موجود؟
✅ main branch تازه است؟ (git fetch origin main)
✅ production_gate.sh pass می‌کند؟
✅ PR #7, #18, #21 بلاک‌کننده‌ای هستند؟
```

---

## ✓ برای هر ایشو: ۵ سؤال

| سؤال | معیار ✅ | معیار ❌ |
|------|---------|----------|
| **عنوان؟** | `fix(scope): دقیق` | `fix: چیز` |
| **شرح؟** | توضیح دقیق + شواهد | "it's broken" |
| **معیار قبول؟** | فایل‌ها + تست‌ها | بدون معیار |
| **پیچیدگی؟** | ۱–۴ فایل (Low–Med) | ۵+ فایل (High) |
| **وابستگی؟** | مستقل | depends on #X |

### اگر همه ✅ → **GO** (شروع کار)
### اگر هر یک ❌ → **STOP** (جواب دهید)

---

## 🔄 فرایند کار (۶ گام)

```bash
# ۱. تماس‌های شاخه
git fetch origin main && git checkout main && git pull
git checkout -b fix/scope-description

# ۲. کدنویسی
# [تغییر فایل‌های مورد نیاز]

# ۳. تست
bash tests/production_gate.sh
# اگر fail: debug و دوباره اجرا
# اگر pass: ادامه

# ۴. commit
git add [files]
git commit -m "type(scope): توضیح

- تغییرات
- شواهد
- gate: production_gate.sh (pass)"

# ۵. push
git push origin fix/scope-description

# ۶. PR
gh pr create --base main --head fix/scope-description \
  --title "type(scope): توضیح" \
  --body "[شامل خلاصه، تغییرات، شواهد]"
```

---

## 🚨 STOP اگر:

```
❌ production_gate.sh fail شود
❌ فایل‌های متأثر > ۵
❌ مستندات قدیمی است
❌ ایشو وابسته است و هنوز باز است
```

---

## ✅ شواهد الزامی

- ✓ `production_gate.sh` pass
- ✓ commit message دقیق
- ✓ PR description مفصل
- ✓ تأیید صادقانهٔ آنچه **نمی‌توانید** خودکار test کنید

---

## 📖 مرجع‌های کامل

- **قرارداد کامل:** `.github/copilot-agent-contract.md`
- **دستور کامل:** `AGENT_OPERATIONAL_DIRECTIVE.md`
- **چک‌لیست:** `AGENT_STARTUP_CHECKLIST.md`

---

**بیشتر برای آموزش؛ این برای شروع.** ⚡

