# 🤖 دستورات Copilot Coding Agent — Mojig62m/Rang

**برای:** GitHub Copilot Coding Agent  
**پروژه:** `https://github.com/Mojig62m/Rang`  
**نسخه:** 1.0  
**تاریخ:** 2026-10-17

---

## 📂 فایل‌های دستور (و کی استفاده کنید)

### **۱. قرارداد اصلی** ← **اول این را بخوانید**

📄 **`copilot-agent-contract.md`** (۳۰۰ خط)

**محتوا:**
- معیار وضوح و مناسب‌بودن ایشو
- شرط‌های دسترسی‌ناپذیری (PR‌های باز)
- فرایند قبل‌از کار (gate، branch، commit)
- شواهد پس‌از کار (الزامی برای merge)
- قوانین fail-closed

**کی بخوانید:**
- ✅ **یک‌بار در شروع** (یادگیری اصول)
- ✅ **برای هر ایشو** (بررسی معیار)
- ✅ **برای هر PR** (اطمینان از شواهد)

**مثال استفاده:**
```
من: آیا می‌توانم روی ایشوی #42 کار کنم؟

→ بخوانم: بخش ۲ (معیار وضوح) ← آیا واضح است؟
→ بخوانم: بخش ۳ (دسترسی‌ناپذیری) ← آیا PR‌های بلاک‌کننده هستند؟
→ بخوانم: بخش ۵ (شواهد) ← چه شواهدی لازم است؟

جواب: ✅ YES or ❌ STOP
```

---

### **۲. دستور اجرایی کامل** ← **چگونه کار کنید**

📄 **`AGENT_OPERATIONAL_DIRECTIVE.md`** (۲۳۰ خط)

**محتوا:**
- فاز ۱: تأیید محیط (یک‌بار)
- فاز ۲: برای هر ایشو (۵ سؤال)
- فاز ۳: فرایند کار (۶ گام)
- الگوهای commit message و PR description
- قوانین fail-closed
- متن‌های الگو برای پاسخ

**کی استفاده کنید:**
- ✅ **بعد از اختصاص ایشو** (برنامهٔ گام‌به‌گام)
- ✅ **اگر نمی‌دانید چطور شروع کنید** (مرجع کامل)

**مثال استفاده:**
```
من: ایشوی #42 برای من assign شد. شروع کنم؟

→ اجرا کنم: مرحلهٔ ۲.۱ (بررسی عنوان)
→ اجرا کنم: مرحلهٔ ۲.۲ (بررسی شرح)
→ ... همه مراحل ...

اگر همه ✅: فاز ۳ (شروع کار)
اگر هر یک ❌: جواب دهم
```

---

### **۳. خلاصهٔ سریع** ← **اگر عجله دارید**

📄 **`AGENT_QUICK_START.md`** (۵۰ خط)

**محتوا:**
- ۳۰ ثانیه‌ای سیاق
- ۵ سؤال برای ایشو
- ۶ گام برای کار
- شواهد الزامی
- خوب و بد

**کی استفاده کنید:**
- ✅ **یادآوری سریع** (یادتان رفت چه کاری انجام دادید؟)
- ✅ **اگر فقط ۲ دقیقه وقت دارید**

---

### **۴. چک‌لیست تفصیلی** ← **برای بررسی دقیق**

📄 **`AGENT_STARTUP_CHECKLIST.md`** (۲۵۰ خط)

**محتوا:**
- فاز ۱: دستورات فنی (curl، git، bash)
- فاز ۲: برای هر ایشو (معیارهای عینی)
- دستورات دقیق bash برای هر چک

**کی استفاده کنید:**
- ✅ **اگر می‌خواهید خودکار کنید** (bash scripts)
- ✅ **برای بررسی دقیق** (معیارهای عینی)

---

### **۵. راهنمای کامیت** ← **برای کامیت فایل قرارداد**

📄 **`MANUAL_COMMIT_GUIDE.md`** (۲۲۰ خط)

**محتوا:**
- گزینهٔ ۱: GitHub Web (سریع‌ترین)
- گزینهٔ ۲: Git CLI (استاندارد)
- گزینهٔ ۳: GitHub Desktop (GUI)
- الگوی PR

**کی استفاده کنید:**
- ✅ **اگر هنوز قرارداد committed نشده** (برای کامیت)
- ✅ **برای اولین دفعه**

---

### **۶. اسکریپت خودکار** ← **اگر می‌توانید bash اجرا کنید**

📄 **`COMMIT_INSTRUCTIONS.sh`** (۱۰۰ خط)

**محتوا:**
- خودکار clone
- خودکار commit
- خودکار push
- خودکار PR

**کی استفاده کنید:**
- ✅ **اگر ترجیح می‌دهید خودکار**
```bash
bash COMMIT_INSTRUCTIONS.sh
```

---

## 🎯 نقشهٔ استفاده (برای ایجنت)

### **روز اول:**
```
۱. بخوانید: copilot-agent-contract.md (تمام)
۲. بخوانید: AGENT_OPERATIONAL_DIRECTIVE.md (فاز ۱ و ۲)
۳. بخوانید: AGENT_QUICK_START.md (خلاصه)

→ حالا آماده شروع هستید
```

### **برای هر ایشو:**
```
۱. بخوانید: AGENT_QUICK_START.md (۵ سؤال)
۲. اگر ✅: اجرا کنید: AGENT_OPERATIONAL_DIRECTIVE.md (فاز ۳)
۳. اگر ❌: پاسخ دهید (الگو در DIRECTIVE)
```

### **اگر نمی‌دانید چه کار کنید:**
```
→ جستجو کنید: AGENT_OPERATIONAL_DIRECTIVE.md
→ یا: AGENT_STARTUP_CHECKLIST.md
→ یا: copilot-agent-contract.md (بخش مرتبط)
```

---

## 📊 ترتیب اهمیت

### **🔴 الزامی (قبل از هر کاری)**
```
۱. copilot-agent-contract.md — تاریخ و معیار
۲. AGENT_OPERATIONAL_DIRECTIVE.md — روند و فرایند
۳. AGENT_QUICK_START.md — یادآوری
```

### **🟡 توصیه شده (برای کار بهتر)**
```
۴. AGENT_STARTUP_CHECKLIST.md — دقت و خودکارسازی
۵. MANUAL_COMMIT_GUIDE.md — اگر قرارداد هنوز commit نشده
```

### **🟢 اختیاری (برای صرفهٔ وقت)**
```
۶. COMMIT_INSTRUCTIONS.sh — اگر دوست دارید خودکار کنید
```

---

## 🚀 شروع سریع (۲ دقیقه)

### برای ایجنت:

```bash
# ۱. چک‌لیست سریع
echo "1️⃣  بررسی قرارداد..."
curl -s https://raw.githubusercontent.com/Mojig62m/Rang/main/.github/copilot-agent-contract.md \
  | head -20

# ۲. بررسی gate
echo "2️⃣  بررسی gate..."
git clone --quiet https://github.com/Mojig62m/Rang.git /tmp/rang
cd /tmp/rang
bash tests/production_gate.sh 2>&1 | tail -5

# ۳. بررسی PR‌های بلاک‌کننده
echo "3️⃣  بررسی PR‌های بلاک‌کننده..."
curl -s https://api.github.com/repos/Mojig62m/Rang/pulls \
  | grep -o '"number": [0-9]*' | grep -E "7|18|21"

echo "✅ اگر همه pass شد: آماده شروع هستید!"
```

---

## 🔍 مثال کامل (شروع تا پایان)

### **صحنه: ایشوی #100 assign شده**

**ایشوی #100:**
```
عنوان: fix(theme): RTL button offset

شرح: موبایل RTL، button offset غلط است. 
      صفحات: cart page. 
      شواهد: screenshot.

معیار قبول:
  - فایل: wp-content/themes/bamero/css/style.css
  - تست: production_gate.sh
  - شاهد: موبایل ۳۲۰px RTL
```

**من (ایجنت):**

```
گام ۱: AGENT_QUICK_START خوانی
  ✅ عنوان: fix(theme): واضح
  ✅ شرح: دقیق است
  ✅ معیار: روشن است
  ✅ پیچیدگی: ۱ فایل (LOW)
  ✅ وابستگی: ندارد

→ GO ✅

گام ۲: AGENT_OPERATIONAL_DIRECTIVE § ۳.۱
  git checkout -b fix/theme-rtl-button-offset

گام ۳: کدنویسی
  تغییر: wp-content/themes/bamero/css/style.css
  اضافه: margin-inline-end: auto;

گام ۴: تست (DIRECTIVE § ۳.۳)
  bash tests/production_gate.sh
  ✅ Gate passed

گام ۵: commit (DIRECTIVE § الگو)
  git commit -m "fix(theme): RTL button offset
  
  - changed: wp-content/themes/bamero/css/style.css
  - added: margin-inline-end: auto
  - evidence: mobile 320px RTL tested ✅
  - gate: production_gate.sh (pass)"

گام ۶: PR (DIRECTIVE § الگو)
  gh pr create ...
  
  شامل:
  - ✅ خلاصه
  - ✅ تغییرات
  - ✅ شواهد
  - ✅ gate output
```

**نتیجه:** PR #1234 merge شد! ✅

---

## 📞 شب‌شنیدن

### **اگر سؤال دارید:**

| سؤال | پاسخ |
|-----|------|
| معیار ایشو چیست؟ | `copilot-agent-contract.md` § ۲ |
| چطور کار کنم؟ | `AGENT_OPERATIONAL_DIRECTIVE.md` § ۳ |
| سریع یادآوری؟ | `AGENT_QUICK_START.md` |
| دقیق‌تر؟ | `AGENT_STARTUP_CHECKLIST.md` |
| بررسی قبل‌از کار؟ | `copilot-agent-contract.md` § ۳–۵ |

---

## ✅ نقطهٔ پایان

**یادتان باشد:**

1. **قرارداد = منبع حقیقت**
   - همه معیارها در `.github/copilot-agent-contract.md` هستند
   - اگر شک دارید: آن را بخوانید

2. **Gate = حافظ**
   - اگر `production_gate.sh` fail شود → stop
   - اگر pass شود → ادامه

3. **شواهد = اعتماد**
   - هرچقدر بیشتر شواهد → merge سریع‌تر
   - صادقانه بگویید آنچه نمی‌توانید تأیید کنید

4. **توقف بهتر از ادامهٔ غلط**
   - اگر مشکوک هستید → سؤال بپرسید
   - اگر نمی‌توانید تأیید کنید → صادقانه بگویید

---

**موفق باشید! 🚀**

